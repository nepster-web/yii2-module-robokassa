<?php

namespace app\modules\merchant\controllers;

use yii\web\HttpException;
use yii\web\Controller;
use Yii;

/**
 * Robokassa Controller
 */
class RobokassaController extends Controller
{
    /**
     * @inheritdoc
     */
    public $enableCsrfValidation = false;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => \yii\filters\AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['success', 'failure'],
                        'roles' => ['@']
                    ],
                    [
                        'allow' => true,
                        'actions' => ['result'],
                        'roles' => ['?']
                    ],
                ]
            ]
        ];
    }

    /**
     * Url адрес взаимодействия
     */
    public function actionResult()
    {
        if (!Yii::$app->request->post()) {
            return $this->goBack();
        }


    }

    /**
     * Успешный платеж
     */
    public function actionSuccess()
    {
        if (!Yii::$app->request->post()) {
            return $this->goBack();
        }


    }

    /**
     * Ошибка платежа
     */
    public function actionFailure()
    {
        if (!Yii::$app->request->post()) {
            return $this->goBack();
        }


    }

    /**
     * Верификация платежа
     */
    protected function verify($data)
    {
        if (Yii::$app->pm->checkHash($data)) {

            // Начисление средств на счет пользователя

            return true;
        }
        return false;
    }

}