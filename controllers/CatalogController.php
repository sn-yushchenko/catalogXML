<?php
namespace app\controllers;

use app\models\Category;
use app\models\Product;
use Yii;
use yii\web\Controller;
use app\models\UploadForm;
use yii\web\UploadedFile;

class CatalogController extends Controller
{
    public static $item = 0;
    public function actionIndex()//загружаем xml каталог
    {
        $model = new UploadForm();

        if (Yii::$app->request->isPost)
        {
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            if ($model->upload()) 
            {
                $this->actionParseCategory();
                $this->actionParseProduct();
                return $this->redirect('/yii2/web/?r=catalog/show');
                
            }
        }
        return $this->render('index', ['model' => $model]);
    }

//парсим xml каталог и заносим данные в таблицы базы данных
    protected function actionParseCategory()
    {

    /*получаем таблицу категорий продуктов*/

        $reader = new \XMLReader();
        $reader->open("../uploads/catalog.xml");
        $count = 0;
        $array = array();
        $data = array();
        while ($reader->read())
        {

            if ($reader->name =="category" && $reader->nodeType == \XMLReader::ELEMENT) 
            {  
                $connection = \Yii::$app->db;
                $data[] = $reader->getAttribute("id");
                $data[] = $reader->readString();

                if($reader->getAttribute("parentID"))
                {
                    $data[] = $reader->getAttribute("parentID");
                }
                else
                {
                    $data[] = 0;
                }
                array_push($array, $data);
                $data = array();
                $count++;
                if($count == 200)
                {
                    $connection->createCommand()->batchInsert('category', ['id','category_name','parent_id'],$array)->execute();
                    $array = array();
                    $count = 0;
                }
            }
        }
        if($count > 0)
        {
            $connection->createCommand()->batchInsert('category', ['id','category_name','parent_id'],$array)->execute();
        }
    }
      /*получаем таблицу  продуктов*/
    protected function actionParseProduct()
    { 
        ini_set ( 'max_execution_time', 10000);
        $reader = new \XMLReader();
        $reader->open("../uploads/catalog.xml");
        $count = 0;
        $array = array();
        while ($reader->read())
        { 
            if ($reader->name =="item" && $reader->nodeType == \XMLReader::ELEMENT) 
            {  
                $connection = \Yii::$app->db;
                $prod = array();
                 $prod[] = $reader->getAttribute('id');
                while(!($reader->name =="item" && $reader->nodeType == \XMLReader::END_ELEMENT))
                {
                    switch($reader->name)
                    {
                    case "name" :
                        if(!($reader->nodeType == \XMLReader::END_ELEMENT))
                        {
                            $prod[] = $reader->readString();//name
                        }
                    break;
                    case "categoryId" : 
                        if(!($reader->nodeType == \XMLReader::END_ELEMENT))
                        {
                            $prod[] = $reader->readString();//category_id
                        }
                    break;
                    case "price" : 
                        if(!($reader->nodeType == \XMLReader::END_ELEMENT))
                        {
                            $prod[] = $reader->readString();//product_price
                        }
                    break;
                    case "vendorCode" :
                        if(!($reader->nodeType == \XMLReader::END_ELEMENT))
                        {
                            $prod[] = $reader->readString();//product_code
                        }
                    break;
                    }
                    $reader->read(); 
                }

            array_push($array,$prod);
            $prod = array();
            $count++;
            if($count == 100)
            {
                $connection->createCommand()->batchInsert('product', ['id','product_code','product_name','category_id','product_price'],$array)->execute();
                $array = array();
                $count = 0;
            }
        }
            
    }
    if($count > 0)
    {
         $connection->createCommand()->batchInsert('product', ['id','product_code','product_name','category_id','product_price'],$array)->execute();
    }

}    
        
    public function getCategories()
    {
        $categories = Category::find()->orderBy(["parent_id"=>SORT_ASC])->asArray()->all();
        $cats = array();
        foreach($categories as $cat)
        {
            $cats[$cat["parent_id"]][$cat["id"]] =  $cat;
        }
            return $cats;
        }
    
    public function getProducts()
    {
        $products = Product::find()->orderBy(["category_id"=>SORT_ASC])->asArray()->all();
        $prod = array();
        foreach($products as $product){
        $prod[$product["category_id"]][$product["id"]] =  $product;
    }
        return $prod;
        
    }
    
    public function actionShow()
    {
        ini_set('memory_limit', '256M');
        $cats = $this->getCategories();
        $products = $this->getProducts();
        $catalog = $this->build_tree($cats,0,$products);
        return $this->render('show', ['catalog' => $catalog]);
    }
    
    
    protected function build_tree($cats,$parent_id,$products,$only_parent=false)
    {
        
        if(is_array($cats) && isset($cats[$parent_id]))
        {
            
            switch(CatalogController::$item){
                case 0: $tree = '<ul class="null">';CatalogController::$item=0;break;
                   case 1: $tree = '<ul class="first">';break;
                    case 2: $tree = '<ul class="second">';break;
                    case 3: $tree = '<ul class="third">';break;
                    case 4: $tree = '<ul class="fourth">';break;
                }
            
            if($only_parent==false){
                foreach($cats[$parent_id] as $cat){
                    $tree .= '<li>'.$cat['category_name'].' #'.$cat['id'];
                    CatalogController::$item++;
                    $tree .=  $this->build_tree($cats,$cat['id'],$products);
                    if(isset($products[$cat["id"]])){
                        $tree .= '<ul>';
                        foreach($products[$cat["id"]] as $product){
                            $tree .= '<li>'.'#'.$product['product_code'].' '.$product['product_name'].' - '.$product['product_price'].' грн.'.'</li>';
                        }
                        $tree .= '</ul>';
                         
                    }
                    
                    $tree .= '</li>';
                }
                
            }
            $tree .= '</ul>';
            CatalogController::$item=0;
        }
    else return null;
    return $tree;
    }

}
?>