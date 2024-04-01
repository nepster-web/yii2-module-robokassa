<?php

namespace nepster\robokassa;

use yii\web\ForbiddenHttpException;
use yii\helpers\ArrayHelper;
use yii\web\HttpException;
use yii\helpers\VarDumper;
use yii\helpers\Url;
use Yii;

/**
 * Class Api
 * @package nepster\robokassa
 */
class Api extends \yii\base\Component
{
    /**
     * @var string
     */
    public $mrchLogin;

    /**
     * @var string
     */
    public $mrchPassword1;

    /**
     * @var string
     */
    public $mrchPassword2;

    /**
     * @var array
     */
    public $resultUrl;

    /**
     * @var array
     */
    public $successUrl;

    /**
     * @var array
     */
    public $failureUrl;

    /**
     * @var bool
     */
    public $isTest = false;

    /**
     * @var string
     */
    private $apiUrl = 'https://auth.robokassa.ru/Merchant/Index.aspx';

    /**
     * @var string
     */
    private $apiServiceUrl = 'https://auth.robokassa.ru/Merchant/WebService/Service.asmx';
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->resultUrl = Url::to($this->resultUrl, true);
        $this->successUrl = Url::to($this->successUrl, true);
        $this->failureUrl = Url::to($this->failureUrl, true);
    }

    /**
     * Создать платеж
     * Генерирует специальный url адрес для оплаты и осуществляет редирект на него
     *
     * @param $nOutSum
     * @param $nInvId
     * @param null $sInvDesc
     * @param null $sIncCurrLabel
     * @param null $sEmail
     * @param null $sCulture
     * @param array $shp
     * @return mixed / redirect to payment url
     */
    public function payment($nOutSum, $nInvId, $sInvDesc = null, $sIncCurrLabel = null, $sEmail = null, $sCulture = null, $shp = [])
    {
        $url = $this->apiUrl;
        $signature = "{$this->mrchLogin}:{$nOutSum}:{$nInvId}:{$this->mrchPassword1}";
        if (!empty($shp)) {
            $signature .= ':' . $this->implodeShp($shp);
        }
        $sSignatureValue = md5($signature);
        $url .= '?' . http_build_query([
                'MrchLogin' => $this->mrchLogin,
                'OutSum' => $nOutSum,
                'InvId' => $nInvId,
                'Desc' => $sInvDesc,
                'SignatureValue' => $sSignatureValue,
                'IncCurrLabel' => $sIncCurrLabel,
                'Email' => $sEmail,
                'Culture' => $sCulture,
            ]);
        if (!empty($shp) && ($query = http_build_query($shp)) !== '') {
            $url .= '&' . $query;
        }

        if ($this->isTest) {
            $url .= '&isTest=1';
        }

        Yii::$app->user->setReturnUrl(Yii::$app->request->getUrl());
        return Yii::$app->response->redirect($url);
    }

    /**
     * Проверка цифровой подписи
     *
     * @param $hash
     * @param $nOutSum
     * @param $nInvId
     * @param $sMerchantPass
     * @param $shp
     * @return bool
     */
    public function checkHash($hash, $nOutSum, $nInvId, $sMerchantPass, $shp)
    {
        $signature = "{$nOutSum}:{$nInvId}:{$sMerchantPass}";
        if (!empty($shp)) {
            $signature .= ':' . $this->implodeShp($shp);
        }
        return strtolower(md5($signature)) === strtolower($hash);
    }

    /**
     * @param $shp
     * @return string
     */
    private function implodeShp($shp)
    {
        ksort($shp);
        foreach ($shp as $key => $value) {
            $shp[$key] = $key . '=' . $value;
        }
        return implode(':', $shp);
    }

    /**
     * Получить доступные методы оплаты
     * Генерирует специальный url адрес для получения методов оплаты и возвращает массив
     *
     * @param string $lang
     * @return array
     */
    public function getPaymentSystemsList($sCulture = 'ru'): array
    {
        $url = $this->apiServiceUrl . '/GetCurrencies';

        $url .= '?' . http_build_query([
                'MerchantLogin' => $this->mrchLogin,
                'Language' => $sCulture,
            ]);

        $items = [];

        // Получаем данные о доступных платежных системах Robokassa
        $xml_str = @file_get_contents($url, 0);

        if (!empty($xml_str)) {
            $movies = new \SimpleXMLElement($xml_str);
            if (isset($movies->Groups->Group)) {
                foreach ($movies->Groups->Group as $group) {
                    if (isset($group->Items->Currency)) {
                        foreach ($group->Items->Currency as $currency) {
                            $items[(string)$currency->attributes()->Label] = [
                                'label' => (string)$currency->attributes()->Label,
                                'alias' => (string)$currency->attributes()->Alias,
                                'name' => (string)$currency->attributes()->Name,
                                'minValue' => isset($currency->attributes()->MinValue) ? (int)$currency->attributes()->MinValue : null,
                                'maxValue' => isset($currency->attributes()->MaxValue) ? (int)$currency->attributes()->MaxValue : null,
                            ];
                        }
                    }
                }
            }
        }

        return $items;
    }
}
