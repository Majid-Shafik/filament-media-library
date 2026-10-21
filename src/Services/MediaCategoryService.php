<?php

namespace MajidShafik\FilamentMediaLibrary\Services;

use MajidShafik\FilamentMediaLibrary\Exceptions\CyclicCategoryException;
use MajidShafik\FilamentMediaLibrary\Models\MediaCategory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class MediaCategoryService
{
    /**
     * Safely assign or change a category's parent with strict cycle prevention and deterministic locking.
     *
     * @param int|MediaCategory $category
     * @param int|null $newParentId
     * @return MediaCategory
     * @throws CyclicCategoryException
     */
    public function assignParent($category, ?int $newParentId): MediaCategory
    {
        $categoryId = $category instanceof MediaCategory ? $category->id : (int) $category;

        return DB::transaction(function () use ($categoryId, $newParentId) {
            // Case 1: Unsetting parent (root category)
            if ($newParentId === null || $newParentId === 0) {
                $catRecord = MediaCategory::where('id', $categoryId)->lockForUpdate()->first();
                if (!$catRecord) {
                    throw new ModelNotFoundException("التصنيف رقم #{$categoryId} غير موجود.");
                }
                $catRecord->parent_id = null;
                $catRecord->save();
                return $catRecord;
            }

            // Case 2: Self parentage check
            if ($categoryId === $newParentId) {
                throw new CyclicCategoryException('لا يمكن للتصنيف أن يكون أباً لنفسه.');
            }

            // Case 3: Identify all involved tree nodes (ancestors and descendants of both branches)
            $catRecord = MediaCategory::find($categoryId);
            if (!$catRecord) {
                throw new ModelNotFoundException("التصنيف رقم #{$categoryId} غير موجود.");
            }

            $descendantIds = $catRecord->getAllDescendantIds()->all();
            $ancestorIdsCat = $this->collectAncestorIds($categoryId);
            $ancestorIdsTarget = $this->collectAncestorIds($newParentId);

            $allInvolvedIds = array_values(array_unique(array_merge(
                [$categoryId, $newParentId],
                $descendantIds,
                $ancestorIdsCat,
                $ancestorIdsTarget
            )));
            sort($allInvolvedIds);

            // Acquire ordered pessimistic locks on ALL involved nodes to prevent cross-branch race conditions
            $lockedNodes = MediaCategory::whereIn('id', $allInvolvedIds)
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if (!isset($lockedNodes[$newParentId])) {
                throw new ModelNotFoundException("التصنيف الأب المستهدف رقم #{$newParentId} غير موجود.");
            }

            // Case 4: Deep ancestry inspection inside locked transaction
            $currentAncestorId = $newParentId;
            $maxDepth = 100;
            $depth = 0;

            while ($currentAncestorId !== null && $depth < $maxDepth) {
                $depth++;
                if ($currentAncestorId === $categoryId) {
                    throw new CyclicCategoryException(
                        "تعارض في الشجرة الهرمية: التصنيف المستهدف (#{$newParentId}) هو أحد الفروع المنحدرة من التصنيف (#{$categoryId})."
                    );
                }

                $parent = $lockedNodes->get($currentAncestorId)
                    ?? MediaCategory::where('id', $currentAncestorId)->lockForUpdate()->first();
                $currentAncestorId = $parent ? $parent->parent_id : null;
            }

            if ($depth >= $maxDepth) {
                throw new CyclicCategoryException('تم تجاوز العمق الأقصى لشجرة التصنيفات (حلقة مفرغة محتملة).');
            }

            $targetCategory = $lockedNodes->get($categoryId);
            $targetCategory->parent_id = $newParentId;
            $targetCategory->save();

            return $targetCategory;
        });
    }

    /**
     * Collect all ancestor IDs for a given node.
     *
     * @param int $startId
     * @return array<int>
     */
    protected function collectAncestorIds(int $startId): array
    {
        $ancestorIds = [];
        $currentId = $startId;
        $depth = 0;

        while ($currentId !== null && $depth < 100) {
            $depth++;
            $row = MediaCategory::where('id', $currentId)->first(['id', 'parent_id']);
            if (!$row || $row->parent_id === null) {
                break;
            }
            $ancestorIds[] = (int) $row->parent_id;
            $currentId = (int) $row->parent_id;
        }

        return $ancestorIds;
    }

    /**
     * Delete a category safely, ensuring it has no children or attached media.
     *
     * @param int|MediaCategory $category
     * @return bool
     * @throws CyclicCategoryException
     */
    public function deleteCategory($category): bool
    {
        $categoryId = $category instanceof MediaCategory ? $category->id : (int) $category;

        return DB::transaction(function () use ($categoryId) {
            $catRecord = MediaCategory::where('id', $categoryId)->lockForUpdate()->firstOrFail();

            // 1. Check for child categories
            $hasChildren = MediaCategory::where('parent_id', $categoryId)->exists();
            if ($hasChildren) {
                throw new CyclicCategoryException('لا يمكن حذف تصنيف يحتوي على تصنيفات فرعية.');
            }

            // 2. Check for attached media (if media table exists)
            if (DB::getSchemaBuilder()->hasTable('media')) {
                $hasMedia = DB::table('media')->where('category_id', $categoryId)->exists();
                if ($hasMedia) {
                    throw new CyclicCategoryException('لا يمكن حذف تصنيف يحتوي على وسائط مرتبطة به.');
                }
            }

            return (bool) $catRecord->delete();
        });
    }
}
