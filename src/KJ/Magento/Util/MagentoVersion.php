<?php

namespace KJ\Magento\Util;

class MagentoVersion
{
    protected $_code = null;

    public function __toString()
    {
        return $this->_code;
    }

    public function __construct($versionCode)
    {
        $this->_code = $versionCode;
    }

    public function getBaseDir()
    {
        $dir = $_SERVER['HOME'] . '/.n98-magerun/version/' . $this->_code;
        $mageFile = $dir . '/app/Mage.php';

        if (! file_exists($mageFile)) {
            throw new \Exception("The $this magento version isn't downloaded yet to $dir
             - download it and uncompress it there.  Download from, e.g.,
             http://www.magentocommerce.com/downloads/assets/1.4.2.0/magento-1.4.2.0.tar.gz");
        }

        return $dir;
    }

    public function getThemePath()
    {
        if ($this->_code < 'ce-1.4') {
            return 'app/design/frontend/default/default';
        } else {
            return 'app/design/frontend/base/default';
        }
    }

    public function getBaseThemeDir()
    {
        return $this->getBaseDir() . '/' . $this->getThemePath();
    }
}