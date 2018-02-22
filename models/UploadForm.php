<?php
namespace app\models;

use yii\base\Model;
use yii\web\UploadedFile;

class UploadForm extends Model
{
    /**
     * @var UploadedFile
     */
    public $imageFile;

    public function rules()
    {
        return [
            [['imageFile'], 'file', 'skipOnEmpty' => false, 'checkExtensionByMimeType'=>false, 'extensions'=>'xml jpg']
        ];
    }
    
    public function upload()
    {
        if ($this->validate()) {
            $this->imageFile->saveAs('../uploads/catalog' . '.' . $this->imageFile->extension);
            return true;
        } else {
            return false;
        }
        
    }
}
?>