<?php

defined("C5_EXECUTE") or die('Access Denied.');

class MiserPackage extends Package {

    protected $pkgHandle = 'miser';
    protected $appVersionRequired = '5.6.1.2';
    protected $pkgVersion = '1.9.2';

    public function getPackageName() {
        return t("Miser");
    }

    public function getPackageDescription() {
        return t("Installs the miser optimization add-on.");
    }

    public function on_start() {
        Events::extend('on_page_output', function($content) {
                $m = Loader::helper('miser', 'miser');
                return $m->optimise($content);
            });
    }

}