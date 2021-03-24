<?php


namespace App\Helpers;


use Illuminate\Support\Facades\Storage;
use phpDocumentor\Reflection\File;
use phpDocumentor\Reflection\Types\This;

class Cart
{
    private $disk = "cart";
    private $file;
    private $fileName = "";
    public function __construct($uId)
    {
        $this->fileName = $uId . ".json";
        $this->setFile();
    }

    public function getCart()
    {
        return $this->file;
    }

    public function changeCart($data)
    {
        if (Storage::disk($this->disk)->exists($this->fileName))
            Storage::disk($this->disk)->delete($this->fileName);
        return Storage::disk($this->disk)->prepend($this->fileName, $data);
    }

    public function setFile()
    {
        $this->file = Storage::disk($this->disk)->exists($this->fileName)
            ? json_decode(Storage::disk($this->disk)->get($this->fileName),true) : [];
    }
}
