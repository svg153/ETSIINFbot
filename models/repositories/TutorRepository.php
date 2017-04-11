<?php
/**
 * Created by PhpStorm.
 * User: Diego
 * Date: 10/4/17
 * Time: 21:02
 */

namespace app\models\repositories;

use app\models\tutor\Tutor;
use Exception;
use Httpful\Request;
use Httpful\Mime;
use Sunra\PhpSimple\HtmlDomParser;

class TutorRepository
{
    public static function getTutor($matricula)
    {
        $request = Request::get("https://www.fi.upm.es/index.php?id=consultatutoria&E_buscar=$matricula")
            ->followRedirects(true)->expects(Mime::HTML)->send();

        if(!$request->hasErrors())
        {

            $dom = HtmlDomParser::str_get_html($request->raw_body);

            if ($dom->find('Debe especificar el número de matrícula completo')){
                print("Debe especificar el número de matrícula completo.\n");
                return null;
            }

            $columns = $dom->find('table tr th');
            $data = $dom->find('table tr td');

            $d1 = [];
            $d2 = [];

            foreach($columns as $campos=>$ths)
            {
                array_push($d1,$ths->innertext);

            }
            foreach($data as $campos=>$tds)
            {
                array_push($d2,$tds->innertext);
            }

            $info = array_combine ($d1,$d2);

            $tutorModel = \Yii::createObject([
                'class' => Tutor::className(),
                'profesor' => $info[0],
                'departamento' => $info[1],
                'despacho'=> $info[2],
                'curso' => $info[3]
            ]);

            if($tutorModel->validate())
            {
                return $tutorModel;
            }

        }
        else
        {
            throw new Exception("Repository exception");
        }
    }

}