<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Controller;

use isv\Controller\ControllerBase;
use isv\IS;
use isv\View\ViewBase;

/**
 * Description of ParseController
 *
 * @author denis
 */
class ParseController extends ControllerBase {

    private $configComp;
    private $mainComp;
    private $parseComp;

    public function init() {
        $this->configComp = new \Component\ConfigComponent();
        $this->mainComp = new \Component\MainComponent();
        $this->parseComp = new \Component\ParseComponent();
    }

    public function indexAction() {

        return new ViewBase();
    }

    public function productAction() {

        $this->parseComp->set('parse_fields', ['img', 'name','diameter','width','profile']);
        $this->parseComp->set('cat', $this->params('id1'));
        $this->parseComp->loadObj($this->params('id'));
        $product_info = $this->parseComp->getInfo();
        $this->mainComp->saveProductFile('/files_with_arrays/' . $this->params('id'), $product_info);
        echo json_encode(true);
        die();
    }

    public function test_imgAction() {
        $this->parseComp->set('parse_fields', ['img']);
        $this->parseComp->set('cat', isset($_GET['cat']) ? $_GET['cat'] : 'tr');
        $this->parseComp->loadObj(isset($_GET['name']) ? $_GET['name'] : 'tr');
        $product_info = $this->parseComp->getInfo();
        return new ViewBase([
            'product_info' => $product_info
                ]
        );
    }

    public function get_json_productsAction() {
        $pr = $this->configComp->set('name', 'products')->get();
        $json_array = [
            'q' => 0,
            'pr' => []
        ];
        if ($pr) {
            foreach ($pr as $k => $v) {
                if ($v && count($v)) {
                    $json_array['q'] += count($v);
                    $json_array['pr'][$k] = $v;
                }
            }
        }

        echo json_encode($json_array);
        die();
    }

    public function parse_priceAction() {
        $url = IS::app()->request()->postData('url');
        if (!$url)
            $this->mainComp->echoJSON(['success' => false, 'msg' => 'Не найдена ссылка']);
        $pr_info = $this->parseComp->set('url', $url)->set('parse_fields', ['products_in_table'])->loadObj()->getInfo();
        $this->mainComp->echoJSON(['success' => $pr_info ? true : false, 'msg' => $pr_info ? 'Успешно' : 'Ошибка']);
    }

    public function parse_price_linksAction() {
        $url = IS::app()->request()->postData('url');
        if (!$url)
            $this->mainComp->echoJSON(['success' => false, 'msg' => 'Не найдена ссылка']);
        $data = [];
        $count = $this->parseComp->set('url',$url)->set('parse_fields', ['count_pages'])->loadObj()->getInfo()['count_pages'];
        $i = 0;
        while ($i <= $count) {
            $data[$i] = $i == 0 ? $url : $url . '&fc_pn=' . $i;
            $i++;
        }
        $this->mainComp->echoJSON(['success' => true, 'data' => $data, 'msg' => 'ok']);
    }

//put your code here
}
