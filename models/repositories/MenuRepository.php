<?php

namespace app\models\repositories;


use app\models\menu\MenuModel;
use Httpful\Mime;
use Httpful\Request;
use yii\base\Exception;
use simplehtmldom_1_5\simple_html_dom;
use simplehtmldom_1_5\simple_html_dom_node;
use Sunra\PhpSimple\HtmlDomParser;

class MenuRepository
{

    private static $months = [
        "enero",
        "febrero",
        "marzo",
        "abril",
        "mayo",
        "junio",
        "julio",
        "agosto",
        "septiembre",
        "octubre",
        "noviembre",
        "diciembre"
    ];


    public static function getMenus()
    {
        $menus = [];
        try
        {
            # Get menu from DA
            $menusDA = self::getMenuFromDA();
            $menus = array_merge($menus, $menusDA);
        }
        catch (\Exception $exception)
        {
            if ($exception->getMessage() == "Unable to parse response as JSON"
                || preg_match('/Unable to connect to /',$exception->getMessage()))
            {
                // send mesage to Alvaro
                $msg = "Parece que el servicio del menu de la web de DAETSIINF esta caida";
                // $this->getRequest()->markdown()->sendMessage($msg."\n\n");
                print($msg);
            }
            else
            {
                throw $exception;
            }
        }

        try
        {
            # Get menus from FI
            $menusFI = self::getMenusFromFI();
            $menus = array_merge($menus, $menusFI);
        }
        catch (\Exception $exception)
        {
            if ($exception->getMessage() == "Unable to parse response as JSON"
                || preg_match('/Unable to connect to /',$exception->getMessage()))
            {
                // send mesage to pmoso
                $msg = "Parece que la web de la facultad esta caida";
                // $this->getRequest()->markdown()->sendMessage($msg."\n\n");
                print($msg);
            }
            else
            {
                throw $exception;
            }
        }

        return $menus;
    }


    private static function getMenuFromDA()
    {
        $request = Request::get("https://da.etsiinf.upm.es/menu/menu.json")->expects(Mime::JSON)->send();
        if (!$request->hasErrors()) {
            $menus = [];
            $data = \GuzzleHttp\json_decode($request->raw_body, true);
            foreach($data as $m){
                $menu = new MenuModel();

                $link = $m['link'];
                $title = $m['name'];
                $vF = $m['validFrom'];
                $vT = $m['validTo'];

                $menu->setAttributes([
                    'link'=>$link,
                    'caption'=>html_entity_decode($title),
                    'validFrom'=>\DateTime::createFromFormat('d-m-y', $vF)->getTimestamp(),
                    'validTo'=>\DateTime::createFromFormat('d-m-y', $vT)->getTimestamp(),
                ]);

                if($menu->validate())
                    $menus[] = $menu;
            }

            return $menus;

        } else {
            throw new Exception("Repository exception");
        }

    }



    private  static function getMenusFromFI()
    {
        $request = Request::get("http://www.fi.upm.es/?pagina=228")->send();
        if(!$request->hasErrors())
        {
            $dom = HtmlDomParser::str_get_html($request->raw_body);
            /** @var $dom simple_html_dom */
            $elems = $dom->find("a.pdf");

            $menus = [];
            foreach($elems as $elem)
            {
                /** @var $elem simple_html_dom_node */
                $text = explode(" ", preg_replace('/[^ \w]+/', '', $elem->innertext()));
                $month = array_search(strtolower(end($text)), self::$months)+1;
                $year = date('Y');

                $days = array_values(array_filter($text, function($e){
                    return intval($e) > 0;
                }));

                $vF = $days[0]."-".$month."-".$year;
                $vT = $days[1]."-".$month."-".$year;

                $menu = new MenuModel();
                $menu->setAttributes([
                    'link'=>"http://www.fi.upm.es/".$elem->getAttribute("href"),
                    'name'=>html_entity_decode($elem->innertext()),
                    'validFrom'=>\DateTime::createFromFormat('d-m-Y', $vF)->getTimestamp(),
                    'validTo'=>\DateTime::createFromFormat('d-m-Y', $vT)->getTimestamp(),
                ]);

                if($menu->validate())
                    $menus[] = $menu;
            }

            return $menus;

        } else {
            throw new Exception("Repository exception");
        }
    }




}