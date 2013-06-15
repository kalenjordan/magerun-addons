<?php

namespace KJ\Magento\Command\Design;

use N98\Magento\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RefreshCommand extends \N98\Magento\Command\AbstractMagentoCommand
{
    /** @var InputInterface $input */
    protected $_input;

    protected function configure()
    {
        $this
            ->setName('design:refresh')
            ->addOption('clear-timestamp', null, InputOption::VALUE_OPTIONAL, 'Clear the timestamp')
            ->setDescription('Change the skin URL in order to force CSS/JS downloads')
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_input = $input;
        $this->detectMagento($output, true);
        $this->initMagento();

        $configs = array(
            'web/unsecure/base_skin_url'    => 'skin',
            'web/secure/base_skin_url'      => 'skin',
            'web/unsecure/base_media_url'   => 'media',
            'web/secure/base_media_url'     => 'media',
            'web/unsecure/base_js_url'    => 'js',
            'web/secure/base_js_url'      => 'js',
        );

        foreach ($configs as $configPath => $type) {
            $value = \Mage::getStoreConfig($configPath);
            $newValue = $this->addNewTimestamp($value, $type);
            $output->writeln("<info>Changing $configPath from $value to $newValue</info>");
            \Mage::getConfig()->saveConfig($configPath, $newValue);
        }
    }

    /**
     * Will match either
     *
     * {{secure_base_url}}skin/
     * http://local.cleanprogram.com/skin/
     * http://local.cleanprogram.com/2013-06-14-11-58-UTC/skin/
     */
    protected function addNewTimestamp($url, $directory)
    {
        if ($this->_input->getOption('clear-timestamp')) {
            $timestamp = '';
        } else {
            $timestamp = date('Y-m-d-g-i-T') . "/";
        }

        $pattern = "(.*)(.com\/|url}}).{0,25}" . $directory ;

        if (preg_match("/" . $pattern . "/", $url)) {
            preg_match_all("/" . $pattern . "/", $url, $matches);
            $newUrl = $matches[1][0] . $matches[2][0] . $timestamp . $directory . "/";
        }

        return $newUrl;
    }
}