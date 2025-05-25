<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

class QaChecklist extends Model
{
    protected $fillable = [
        'feature_name',
        'feature_category',
        'feature_subcategory',
        'test_case',
        'url',
        'test_steps',
        'expected_result',
        'test_type',
        'priority',
        'status',
        'notes',
        'error_details',
        'tester_name',
        'test_date',
        'environment',
        'browser',
        'device'
    ];

    protected $casts = [
        'test_date' => 'date'
    ];

    /**
     * Get all feature categories
     *
     * @return array
     */
    public static function getFeatureCategories()
    {
        return array_keys(Config::get('qa_categories.categories'));
    }

    /**
     * Get subcategories for a specific category
     *
     * @param string $category
     * @return array
     */
    public static function getSubcategories($category)
    {
        $categories = Config::get('qa_categories.categories');
        if (!isset($categories[$category])) {
            return [];
        }
        return array_keys($categories[$category]);
    }

    /**
     * Get features for a specific subcategory
     *
     * @param string $category
     * @param string $subcategory
     * @return array
     */
    public static function getFeatures($category, $subcategory)
    {
        $categories = Config::get('qa_categories.categories');
        if (!isset($categories[$category][$subcategory])) {
            return [];
        }
        return $categories[$category][$subcategory];
    }

    /**
     * Get all categories with their subcategories
     *
     * @return array
     */
    public static function getAllCategoriesWithSubcategories()
    {
        $categories = Config::get('qa_categories.categories');
        $result = [];

        foreach ($categories as $category => $subcategories) {
            $result[$category] = array_keys($subcategories);
        }

        return $result;
    }
}
