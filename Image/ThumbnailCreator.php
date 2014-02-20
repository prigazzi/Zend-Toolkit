<?php
/**
 * Thumbnails creation Service for poll-uploaded Images.
 * 
 * Basic functionality and assumptions:
 *
 * 1) Thumbnails filenames are predefined by the current architecture. We need the Id
 *    inside the filename.
 *
 * 2) Different Thumbnails are for width and/or name changes
 * 
 * 3) Thumbnails go inside the same folder were original files are stored, for now.
 * 
 * Basic Usage - create all thumbnails
 * 
 * $thumbnailCreator = new Service_ThumbnailCreator($filename, $path);
 * $thumbnailCreator->createThumbnails();
 * 
 * 
 * Creating specific thumbnails
 * 
 * $thumbnailCreator = new Service_ThumbnailCreator($filename, $path);
 * $thumbnailCreator->createThumbnail('small');
 * 
 *
 * Defining thumbnails widths and tags
 * 
 * $thumbnailCreator = new Service_ThumbnailCreator($filename, $path);
 * $thumbnailCreator->setWidths(array('small' => 150, 'medium' => 200, 'xl' => 500));
 * 
 * 
 * Creating thumbnails for more than one image
 * 
 * $thumbnailCreator = new Service_ThumbnailCreator();
 * $thumbnailCreator->createThumbnails($filename1, $path1);
 * $thumbnailCreator->createThumbnails($filename2, $path2);
 * $thumbnailCreator->createThumbnails($filenameN, $pathN);
 * 
 */
class Toolkit_Image_ThumbnailCreator {
    protected $filename = '';
    protected $targetPath = '';
    protected $widths = array('small' => 210, 'medium' => 452);
    protected $imageResizer;
    

    /**
     * Constructor. You can specify one fixed image name and path, for ease of use.
     *
     * @param string $filename
     * @param string $targetPath 
     */
    public function __construct($filename = '', $targetPath = '')
    {
        $this->setTargetFile($filename, $targetPath);
    }
    
    /**
     * Creates all possible thumbnails, as many as $widths array elements.
     * It's possible to set the $widths array prior calling this method.
     *
     * @param string $filename
     * @param string $targetPath
     * @return array
     */
    public function createThumbnails($filename = '', $targetPath = '')
    {
        $this->setTargetFile($filename, $targetPath);
        $this->assertFilenameSet();
        
        /* default behaviour: thumbs creation for all defined widths.*/
        $createdFilenames = array('original' => $this->filename);
        foreach($this->widths as $size => $width) {
            $filename = $this->createThumbnail($size);
            
            if($filename !== false) {
                $createdFilenames[$size] = $filename;
            }
        }
        
        return $createdFilenames;
    }
    

    /**
     * Creates a specific size thumb, must be present at widths array.
     *
     * @param string $size
     * @param string $filename
     * @param string $targetPath
     * @return string
     */
    public function createThumbnail($size, $filename = '', $targetPath = '') {
        $this->setTargetFile($filename, $targetPath);
        $this->assertFilenameSet();        
        
        $this->performImageProcessing($size);
        return $this->saveImage($size);
    }


    protected function setTargetFile($filename, $targetPath = '') {
        if(!$filename) {
            return;
        }
        
        $this->filename = $filename;
        $this->targetPath = $targetPath;

        $this->imageResizer = new Toolkit_Image_ImageResizer($this->targetPath . $this->filename);
        
        self::assertReadable($this->targetPath . $this->filename);        
    }
    

    protected function performImageProcessing($size) {
        /* For now, ImageProcessinsg justs changes width. 
         * Will support different types of precessing in the future
         */
        $this->imageResizer->resizeToWidth($this->getWidth($size));        
    }
    

    protected function saveImage($size) {
        $filename = $this->getFilename($size);
        $fullPath = $this->targetPath . $filename;
        
        $this->imageResizer->save($fullPath);
        
        return is_readable($fullPath) ? $filename : false;
    }
    

    protected function getFilename($size) {
        /**
         * For now, the filename uses the image id and size
         */
        if(!preg_match("#^([\d]*)-(.*)\.(.*)$#", $this->filename, $matches)) {
            throw new Exception("No se pudo parsear el nombre de la imagen '{$this->filename}', para generar los nombres de los thumbnails.");
        }
        
        $id = $matches[1];
        $name = $matches[2];
        $extension = $matches[3];
        
        return "{$id}_{$size}_{$name}.{$extension}";
    }
    

    protected function getWidth($width)
    {
        if(!isset($this->widths[$width]))
            throw new Exception("No se encuentra el ancho solicitado '{$width}' para generar thumbnail.");
        
        return $this->widths[$width];
    }
    

    protected static function assertReadable($path)
    {
        if(!is_readable($path)) {
            throw new Exception("La imagen '{$path}' no existe o faltan permisos para acceder.");
        }
    }
    

    protected function assertFilenameSet()
    {
        if($this->filename === '') {
            throw new Exception("No se ha definido una imagen para crear thumbnails.");
        }
    }
    

    public function setWidths($widths) {
        $this->widths = $widths;
    }

}
