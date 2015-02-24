<?php
namespace KJ\Magento\Util\Comparison;

use KJ\Magento\Util\Comparison\Filter\OnlyDifferent;
use KJ\Magento\Util\Comparison\Filter\OnlyEqual;
use KJ\Magento\Util\Comparison\Filter\Null;
use KJ\Magento\Util\ThemeComparison\AbstractThemeComparisonItem;

abstract class Filter
{
    const NONE = 'none';
    const ONLY_DIFFERENT = 'only-different';
    const ONLY_EQUAL = 'only-equal';

    /**
     * Abstract factory
     *
     * @param $value
     * @return Filter
     */
    public static function factory($value)
    {
        switch ($value) {
            case self::ONLY_DIFFERENT:
                return new OnlyDifferent();
            case self::ONLY_EQUAL:
                return new OnlyEqual();
            default:
                return new Null();
        }
    }

    /**
     * Return true if item should be included in filtered result
     *
     * @param AbstractThemeComparisonItem $item
     * @return bool
     */
    abstract public function filterItem(AbstractThemeComparisonItem $item);
}