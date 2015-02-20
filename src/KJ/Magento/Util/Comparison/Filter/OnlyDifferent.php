<?php
namespace KJ\Magento\Util\Comparison\Filter;

use KJ\Magento\Util\Comparison\Filter;
use KJ\Magento\Util\ThemeComparison\AbstractThemeComparisonItem;

class OnlyDifferent extends Filter
{
    /**
     * Return true if item should be included in filtered result
     *
     * @param AbstractThemeComparisonItem $item
     * @return bool
     */
    public function filterItem(AbstractThemeComparisonItem $item)
    {
        return $item->getNumberOfDifferences() > 0;
    }

}