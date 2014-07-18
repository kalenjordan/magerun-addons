<?php

namespace KJ\Magento\Command\GiftCard;

use N98\Magento\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCodesCommand extends \N98\Magento\Command\AbstractMagentoCommand
{
    /** @var InputInterface $input */
    protected $_input;

    /** @var OutputInterface $output  */
    protected $_output;

    protected function configure()
    {
        $this
            ->setName('giftcard:generate-codes')
            ->addArgument('website-id', InputArgument::REQUIRED, 'The website ID')
            ->addArgument('balance', InputArgument::REQUIRED, 'The amount of dough to put on the gift card')
            ->addArgument('quantity', InputArgument::REQUIRED, 'The number of codes to generate')
            ->addOption('status', null, InputOption::VALUE_OPTIONAL, 'The gift card code status (enabled, disabled)', 'enabled')
            ->addOption('state', null, InputOption::VALUE_OPTIONAL, 'The state of the gift card code (available, used, redeemed, expired) ', 'available')
            ->addOption('prefix', null, InputOption::VALUE_OPTIONAL, 'The gift card code prefix', null)
            ->addOption('dashes-every', null, InputOption::VALUE_OPTIONAL, 'Dashes every X characters', 4)
            ->addOption('length', null, InputOption::VALUE_OPTIONAL, 'The number of characters in the code', 12)
            ->addOption('expires', null, InputOption::VALUE_OPTIONAL, 'Expiration date of codes', null)
            ->addOption('is-redeemable', null, InputOption::VALUE_OPTIONAL, 'Whether the gift card code is redeemable', 1)
            ->addOption('dry-run', null, InputOption::VALUE_OPTIONAL, 'Whether to do a dry run or actually generate', 1)
            ->setDescription('Generate gift card codes for the EE_GiftCardAccount module.')
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
        $this->_output = $output;

        $this->detectMagento($output, true);
        $this->initMagento();

        $this->_generateCodes();
    }

    protected function _getRandomCharacter()
    {
        $cryptKey = \Mage::getConfig()->getNode('global/crypt/key');
        return strtoupper(substr(md5(microtime() . $cryptKey), 0, 1));
    }

    protected function _generateCode()
    {
        $code = $this->_input->getOption('prefix');
        $dashesEvery = $this->_input->getOption('dashes-every');

        for ($i = 0; $i < $this->_input->getOption('length'); $i++) {
            $char = $this->_getRandomCharacter();
            if ($dashesEvery > 0 && ( $i % $dashesEvery) == 0 && $i != 0) {
                $char = "-{$char}";
            }

            $code .= $char;
        }

        return $code;
    }

    protected function _generateCodes()
    {
        $status = $this->_input->getOption('status');
        $state = $this->_input->getOption('state');
        $expires = $this->_input->getOption('expires');
        $isRedeemable = $this->_input->getOption('is-redeemable');

        $this->_output->writeln("Status: <info>$status</info>");
        $this->_output->writeln("State: <info>$state</info>");
        $this->_output->writeln("Expires: <info>" . (($expires) ? $expires : "never") .  "</info>");
        $this->_output->writeln("Is Redeemable: <info>" . (($isRedeemable) ? "yes" : "no") .  "</info>");
        $this->_output->writeln("Website ID: <info>" . $this->_input->getArgument('website-id') .  "</info>");
        $this->_output->writeln("Balance: <info>" . \Mage::app()->getLocale()->currency( $currency_code )->getSymbol() . $this->_input->getArgument('balance') .  "</info>");

        if ($this->_input->getOption('dry-run')) {
            $this->_output->writeln("\r\n<info>Creating " . $this->_input->getArgument('quantity') . " code(s) (dry run)</info>\r\n");
            $this->_output->writeln("Example code: <info>" . $this->_generateCode() . "</info>");
        } else {
            $this->_output->writeln("\r\n<info>Creating " . $this->_input->getArgument('quantity') . " LIVE codes</info>\r\n");
            $this->_generateLiveCodes();
        }

        $this->_output->writeln("");
    }

    protected function _generateLiveCodes()
    {
        for ($i = 0; $i < $this->_input->getArgument('quantity'); $i++) {
            $this->_generateLiveCode($i + 1);
        }
    }

    protected function _getStatus()
    {
        $statusMapping = array(
            'enabled' => \Enterprise_GiftCardAccount_Model_Giftcardaccount::STATUS_ENABLED,
            'disabled' => \Enterprise_GiftCardAccount_Model_Giftcardaccount::STATUS_DISABLED,
        );

        if (! isset($statusMapping[$this->_input->getOption('status')])) {
            throw new \Exception("Unable to find status: " . $this->_input->getOption('status'));
        }

        return $statusMapping[$this->_input->getOption('status')];
    }

    protected function _getState()
    {
        $mapping = array(
            'available'     => \Enterprise_GiftCardAccount_Model_Giftcardaccount::STATE_AVAILABLE,
            'expired'       => \Enterprise_GiftCardAccount_Model_Giftcardaccount::STATE_EXPIRED,
            'redeemed'      => \Enterprise_GiftCardAccount_Model_Giftcardaccount::STATE_REDEEMED,
            'used'          => \Enterprise_GiftCardAccount_Model_Giftcardaccount::STATE_USED,
        );

        if (! isset($mapping[$this->_input->getOption('state')])) {
            throw new \Exception("Unable to find state: " . $this->_input->getOption('state'));
        }

        return $mapping[$this->_input->getOption('state')];
    }

    protected function _generateLiveCode($indexStartingAtOne)
    {
        $code = $this->_generateCode();
        $this->_output->writeln("$indexStartingAtOne. Creating Code: <info>$code</info>");

        $existingCode = \Mage::getModel('enterprise_giftcardaccount/giftcardaccount')->loadByCode($code);
        if ($existingCode->getId()) {
            throw new \Exception("This code already exists: $code");
        }

        \Mage::getModel('enterprise_giftcardaccount/giftcardaccount')
            ->setCode($code)
            ->setStatus($this->_input->getOption('status'))
            ->setStatus($this->_getStatus())
            ->setDateCreated(now())
            ->setDateExpires($this->_input->getOption('expires'))
            ->setWebsiteId($this->_input->getArgument('website-id'))
            ->setBalance($this->_input->getArgument('balance'))
            ->setState($this->_getState())
            ->setIsRedeemable($this->_input->getOption('is-redeemable'))
            ->save();

        return $this;
    }
}
