<?php

namespace Modules\Auth\Enums;

enum AccountType: string
{
    case PERSONAL = 'personal';
    case ORGANIZATION = 'organization';

    public function label(): string
    {
        return match ($this) {
            self::PERSONAL => 'Personal',
            self::ORGANIZATION => 'Organization',
        };
    }

    /**
     * @return array<string>
     */
    public function categories(): array
    {
        return match ($this) {
            self::PERSONAL => [
                'Individual',
                'Believer',
                'Pastor',
                'Elder',
                'Fellowship Leader',
                'Intercessor',
                'Prophet',
                'Apostle',
                'Worship Leader',
                'Teacher',
            ],
            self::ORGANIZATION => [
                'Local Church',
                'Non-Profit',
                'Fellowship Group',
                'Media / Publishing',
                'Business',
                'Charity',
                'College',
            ],
        };
    }

    /**
     * @return array<string>
     */
    public static function allCategories(): array
    {
        return array_values(array_unique(array_merge(
            self::PERSONAL->categories(),
            self::ORGANIZATION->categories(),
        )));
    }

    /**
     * @return array<string>
     */
    public static function categoriesFor(string|AccountType|null $accountType): array
    {
        if ($accountType instanceof self) {
            return $accountType->categories();
        }

        $type = self::tryFrom($accountType ?? '');

        return $type ? $type->categories() : self::allCategories();
    }
}

