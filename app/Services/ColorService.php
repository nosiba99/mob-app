<?php

namespace App\Services;

use App\Models\Color;

class ColorService
{
    public function getAll()
    {
        return Color::all();
    }

    public function create(array $data)
    {
        $exists = Color::where('name', $data['name'])
                       ->where('code', $data['code'])
                       ->exists();

        if ($exists) {
            return false;
        }

        return Color::create([
            'name' => $data['name'],
            'code' => $data['code'],
        ]);
    }

    public function delete(Color $color)
    {
        $color->delete();
        return true;
    }
}
