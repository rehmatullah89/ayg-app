<?php
namespace App\Background\Mappers\Partners\Grab;


class ItemTypeAndCategoryMapper
{
    public static function mapType(string $type): string
    {
        $array = [
            'Food' => 'Food',
            'Retail' => 'Retail',
            'Services' => 'Service',
            'All' => 'Food',
            '' => 'Food', // default
        ];

        return $array[$type];
    }

    public static function mapCategory(string $category): string
    {
        $array = [
            'Accessories' => 'Convenience',
            'American' => 'American',
            'Art' => 'Convenience',
            'Asian' => 'Asian',
            'Bakery' => 'Bakery',
            'Bar' => 'Bar',
            'BBQ' => 'American',
            'Beauty' => 'Personal Care',
            'Beauty Retail' => 'Personal Care',
            'Books' => 'Convenience',
            'British' => 'Sandwich',
            'Cajun' => 'American',
            'Candy' => 'Convenience',
            'Caribbean' => 'Sandwich',
            'Clothing' => 'Clothing',
            'Coffee & Drinks' => 'Coffee/Tea',
            'Cuban' => 'Sandwich',
            'Currency Exchange' => 'Financial',
            'Deli' => 'Sandwich',
            'Desserts' => 'Dessert',
            'Duty' => 'Duty Free',
            'Duty Free' => 'Duty Free',
            'Electronics' => 'Electronics',
            'French' => 'Sandwich',
            'Gifts' => 'Gifts',
            'Global' => 'Sandwich',
            'Greek' => 'Sandwich',
            'Healthy' => 'Salads',
            'Indian' => 'Soup',
            'Italian' => 'Italian',
            'Kosher' => 'Sandwich',
            'Latin' => 'Sandwich',
            'Lounge' => 'Lounge',
            'Luggage' => 'Luggage',
            'Mediterranean' => 'Mediterranean',
            'Mexican' => 'Mexican',
            'Middle Eastern' => 'Sandwich',
            'News' => 'Newsstand',
            'Peruvian' => 'Sandwich',
            'Pub' => 'American',
            'Seafood' => 'Seafood',
            'Shoe Shine' => 'Personal Care',
            'Show' => 'Convenience',
            'Smoothies' => 'Smoothies',
            'Snacks' => 'Snacks',
            'Spa' => 'Spa',
            'Wine' => 'Convenience',
            'Show All' => 'Snacks',
            'Japanese' => 'Japanese',
            'Retail' => 'Convenience',
            'Wine Bar' => 'Convenience',
            '' => 'Fast Food', // default
        ];

        return $array[$category];
    }
}
