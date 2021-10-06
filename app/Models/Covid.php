<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class Covid extends Model
{
    use HasFactory;

    private function consultarBrasilIO(Request $request)
    {

        $state = $request->state;
        $dateStart = $request->dateStart;
        $dateEnd = $request->dateEnd;
        if (!$state) {
                return array(
                'id' => 1,
                'Status' => 'Favor preencher State!'
            );
        }
        if ($dateStart && !$this->validateDate($dateStart)) {
            return array(
                'id' => 1,
                'Status' => 'Data Start Inválida!'
            );
        }
        if ($dateEnd && !$this->validateDate($dateEnd)) {
            return array(
                'id' => 1,
                'Status' => 'Data Start Inválida!'
            );
        }
        $url  = "https://brasil.io/api/dataset/covid19/caso/data/?state=$state&dateStart=$dateStart&dateEnd=$dateEnd";
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_HTTPHEADER => array(
                "Authorization: Token 594f27c94a6d3c2064ae027fefb9c784f8afe8ce",
                "Content-type: application/json"
            ),
        ));

        $response = json_decode(curl_exec($curl));
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return array(
                'id' => 1,
                'Status' => 'Erro na requisição ' . $err
            );
        } else {
            if($response->message){
                return array(
                    'id' => 1,
                    'Status' => $response->message
                ); 
            }
            return $response;
        }

       
    }



    public function ranking(Request $param)
    {
        $consult = $this->consultarBrasilIO($param);
        if (is_array($consult) && isset($consult['id'])) {
            return $consult;
        }
        $json = $consult->results;
        foreach ($json as $key) {
            // echo "$key->city<br>$key->confirmed<br>$key->estimated_population<br><br>----<br>";
            if ($key->estimated_population > 0) {
                $percentualCasos = $key->confirmed / $key->estimated_population * 100;
            } else {
                $percentualCasos = 0;
            }
            $key->percentualCasos = $percentualCasos;
        }
        usort($json, function ($a, $b) {
            return $a->percentualCasos > $b->percentualCasos ? -1 : 1;
        });

        for ($i = 0; $i < 10; $i++) {
            $element = $json[$i];
            $jsonCidade = array(
                'id' => $i,
                'nomeCidade' => $element->city,
                'percentualDeCasos' => $element->percentualCasos,
            );
            $return = $this->requestMestra($jsonCidade);
            if ($return['id'] == '1') {
                return $return;
            }
        }
        return array(
            'id' => 0,
            'Status' => 'Procedimentos realizado com sucesso!'
        );
    }

    private function requestMestra($param)
    {


        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://us-central1-lms-nuvem-mestra.cloudfunctions.net/testApi",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_HTTPHEADER => array(
                "MeuNome: Talles Victor",
            ),
            CURLOPT_POSTFIELDS => $param
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return array(
                'id' => 1,
                'Status' => 'Erro na requisição ' . $err
            );
        } else {
            return array(
                'id' => 0,
                'Status' => 'Requisição realizada com sucesso!'
            );
        }
    }

    private function validateDate($date, $format = 'Y-m-d')
    {
        $d = DateTime::createFromFormat($format, $date);
        // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
        return $d && $d->format($format) === $date;
    }
}
