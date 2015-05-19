<?php

namespace app\modules\merchant\widgets\robokassa;

use yii\base\Widget;
use yii\web\View;
use Yii;

class RenderForm extends Widget
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        return $this->render('form', [

        ]);
    }
}