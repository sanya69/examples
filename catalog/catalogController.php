<?php

class catalogController
{
    public function showCatalog()
    {
        require('./catalogModel.php');
        $model = new catalogModel;
        
        // получение данные из модели
        $products = $model->getProducts();
        $filters = $model->getFilters();
        $pagination = $model->getPagination();
        
        // вызов шаблона
        require('./catalogView.php');
    }
}
