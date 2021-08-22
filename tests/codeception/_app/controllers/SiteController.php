<?php

/**
 * Created by PhpStorm.
 * User: brom
 * Date: 11/11/15
 * Time: 5:43 PM
 */

namespace asmbr\wallet\tests\codeception\_app\controllers;

use yii;
use yii\web\Controller;

class SiteController extends Controller
{
    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionTest()
    {
        return $this->redirect('https://www.google.com.ua');
    }


}