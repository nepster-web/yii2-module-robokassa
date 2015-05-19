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
    public $mrchPassword;

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
     * @var string
     */
    private $apiUrl = 'https://auth.robokassa.ru/Merchant/Index.aspx';

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
        $signature = "{$this->mrchLogin}:{$nOutSum}:{$nInvId}:{$this->mrchPassword}";
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
}