<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\Storage;

class CategoryService
{
    // جلب كل الأقسام
    public function getAll()
    {
        return Category::orderBy('id', 'DESC')->paginate(20);
    }

    // إنشاء قسم جديد
    public function create(array $data)
    {
        $path = null;

        if (isset($data['image'])) {
            $path = $data['image']->store('categories', 'public');
        }

        return Category::create([
            'name'  => $data['name'],
            'image' => $path,
        ]);
    }

    // جلب قسم واحد
    public function getById($id)
    {
        return Category::find($id);
    }

    // تحديث قسم
    public function update(Category $category, array $data)
    {
        // تحديث الاسم
        if (isset($data['name'])) {
            $category->name = $data['name'];
        }

        // تحديث الصورة
        if (isset($data['image'])) {

            // حذف القديمة
            if ($category->image && Storage::disk('public')->exists($category->image)) {
                Storage::disk('public')->delete($category->image);
            }

            // رفع الجديدة
            $category->image = $data['image']->store('categories', 'public');
        }

        $category->save();

        return $category;
    }

    // أرشفة قسم
    public function archive($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return false;
        }

        $category->delete();
        return true;
    }

    // جلب المؤرشفين
    public function getArchived()
    {
        return Category::onlyTrashed()->paginate(20);
    }

    // استعادة قسم
    public function restore($id)
    {
        $category = Category::onlyTrashed()->find($id);

        if (!$category) {
            return false;
        }

        $category->restore();
        return $category;
    }
}
