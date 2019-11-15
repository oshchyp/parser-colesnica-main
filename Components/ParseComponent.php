<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Component;

use isv\Component\ISVComponent;
use isv\Component\ISVComponentInterface;
use isv\IS;

/**
 * Description of ParseComponent
 *
 * @author denis
 */
class ParseComponent extends ISVComponent implements ISVComponentInterface {
    
    private $url = 'https://b2b.pwrs.ru/Product/ProductCard?cae=';
    private $parse_fields = ['img','name'];
    private $result = false;
    private $pageObj = false;
    private $cat = false;
    private $cookieFile;


    public function init() {
        require_once ROOTDIR.'/vendor/simple_html_dom/simple_html_dom.php';
        $this->cookieFile = ROOTDIR.'/'.IS::app()->getConfig('config')['publicDir'].'/files/cookie.txt';
    }

    public function set($k, $v) {
        $this->$k = $v;
        return $this;
    }

    public function get($k) {
        return $this->$k;
    }

    public function load($data = []) {
        if (count($data))
            foreach ($data as $k => $v)
                $this->$k = $v;
        return $this;
    }
    
    public function getCURL($url){
            $ch = curl_init(); 
            curl_setopt($ch, CURLOPT_URL, $url); 
            curl_setopt($ch, CURLOPT_HEADER, false); 
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
            $result = curl_exec($ch); 
            curl_close($ch);
            return $result;
    }
    
    public function loadObj ($str='') {
        if ($html = $this->getCURL($this->url.$str)){
            $this -> pageObj = str_get_html($html);
        }
        return $this;
    }
    
    public function getInfo(){
        $result = [];
        foreach ($this -> parse_fields as $v){
            $methodName = 'get'.ucfirst($v);
            $result[$v] = $this -> pageObj ? $this ->  $methodName() : false;
        }
        return $result;
    }
    
    public function getImg() {
        $obj = $this->pageObj->find('table img', 0);
        $img_url = $obj ? $obj->src : false;
        if ($img_url) {
            $float_dir = ROOTDIR . '/' . IS::app()->getConfig('config')['publicDir'] . '/files/images';
            $img_url = explode('?w=', $img_url)[0];
            $file_name = str_replace(['https://www.4tochki.ru/','pictures/','/','.png'],['','','_','_png.jpg'],$img_url);
            $file_dir = ROOTDIR . '/' . IS::app()->getConfig('config')['publicDir'] . '/files/images/products';
            $file_resurce = $this->getCURL($img_url);
            if ($file_resurce) {
                $img_setObj = new ConfigComponent();
                $img_set = $img_setObj->set('key', 'img')->get();
                $imgComp = new ImgComponent();
                file_put_contents($file_dir . '/' . $file_name, $file_resurce);
                $imgComp->get('obj')->load($file_dir . '/' . $file_name, $file_resurce);
                if (isset($img_set[$this->cat])) {
                        $img_set[$this->cat]['top'] = (float)$img_set[$this->cat]['top'] > 0 ? (float) $img_set[$this->cat]['top'] : false;
                        $img_set[$this->cat]['left'] = (float)$img_set[$this->cat]['left'] > 0 ? (float) $img_set[$this->cat]['left'] : false;
                        $imgComp->get('obj')->watermark($float_dir.'/'.$img_set['img'],false,$img_set[$this->cat]['left'],$img_set[$this->cat]['top']);
                }
                $imgComp->get('obj')->save($file_dir . '/' . $file_name); 
                    return $file_name;
            }
        }
        return false;
    }

    public function getName(){
        $obj = $this -> pageObj->find('table tr',1);
        if ($obj->find('td',1)){
            return  trim($obj->find('td',1)->text());
        }
        return false;
    }
    
     public function getWidth(){
        $obj = $this -> pageObj->find('table tr');
        if ($obj){
            foreach ($obj as $v){
               $td_name = $v->find('td',0);$td_val = $v->find('td',1);
                if ($td_name && $td_val){
                    if (trim($td_name->text()) == 'Ширина')
                        return trim($td_val -> text());
                }
            }
        }
        return false;
    }
    
     public function getDiameter(){
        $obj = $this -> pageObj->find('table tr');
        $d = ''; $c='';
        if ($obj){
            foreach ($obj as $v){
                $td_name = $v->find('td',0);$td_val = $v->find('td',1);
                if ($td_name && $td_val){
                    if (trim($td_name->text()) == 'Диаметр')
                        $d = trim($td_val -> text());
                     if (trim($td_name->text()) == 'Конструкция шины')
                        $c = trim($td_val -> text());
                }
            }
        }
        return $c.$d;
     }
     
      public function getProfile(){
         $obj = $this -> pageObj->find('table tr');
        if ($obj){
            foreach ($obj as $v){
                $td_name = $v->find('td',0);$td_val = $v->find('td',1);
                if ($td_name && $td_val){
                    if (trim($td_name->text()) == 'Профиль')
                        return trim($td_val -> text());
                }
            }
        }
        return false;
     }
    
    public function getProduct_url(){
        $obj = $this-> pageObj ->find('a',0);
        $url = $obj ? trim($obj -> href) : false;
        return $url ? 'https://www.4tochki.ru'.$url : false;
    }
    
    public function getCount_pages() {
        $url = false;
        $count = 1;
        if ($obj = $this->pageObj->find('div[class=col-md-10 p-l-0 p-r-0] ul.pagination li'))
            foreach ($obj as $v) {
                if ($v->find('a', 0))
                    $url = 'https://b2b.pwrs.ru' . $v->find('a', 0)->href;
            }
        if ($url) {
            $url_exp = explode('fc_pn=', $url);
            if (count($url_exp) > 1)
                $count = (int) $url_exp[1];
        }
        \isv\Developer\Developer::dump($url);
        return $count;
    }
    
    public function getProducts_in_table() {
        $mainObj = new MainComponent();
        $pr = false;
        if ($obj = $this->pageObj->find('div.table-wrap table[class=table table-1] tbody tr')) {
            foreach ($obj as $k => $v) {
                if ($qObj = $v->find('td a.sae-link', 0)) {
                    $pr[$k]['article'] = trim($qObj->text());
                    if ($priceObj = $v->find('td', 4))
                        $pr[$k]['price'] = (float) preg_replace("/[^0-9]/", '', $priceObj->text());
                    if ($perpayObj = $v->find('td', 5))
                        $pr[$k]['prepayment'] = (float) preg_replace("/[^0-9]/", '', $perpayObj->text());
                    if ($qObj = $v->find('td', 6))
                        $pr[$k]['q'] = (int) preg_replace("/[^0-9]/", '', $qObj->text());
                    
                    $mainObj->saveProductFile('/files_with_arrays/'.$pr[$k]['article'], $pr[$k]);
                }
            }
        }
        return $pr;
    }

}
