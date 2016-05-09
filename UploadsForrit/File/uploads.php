<?php
    namespace UploadsForrit\File;
    class Upload {
        protected $destination;
        protected $renameDuplicates;
        protected $max = 5120000;
        protected $messages = [];
        protected $permitted = [
            'image/gif',
            'image/jpeg',
            'image/png'
        ];
        public function __construct($path) {
            if (!is_dir($path) || !is_writable($path)) {
                throw new \Exception("$path must be a valid,writable directory.");
            }
            $this->destination = $path;
        }
        public function upload($renameDuplicates = true) {

            $uploaded = current($_FILES);
            if ($this->checkFile($uploaded)) {
                $this->moveFile($uploaded);
            }
            if ($this->renameDuplicates) {
                $name = isset($this->newName) ? $this->newName : $file['name'];
                $existing = scandir($this->destination);
                if (in_array($name, $existing)) {
                    // rename file
                    $basename = pathinfo($name, PATHINFO_FILENAME);
                    $extension = pathinfo($name, PATHINFO_EXTENSION);
                    $i = 1;
                    do {
                        $this->newName = $basename . '_' . $i++;
                        if (!empty($extension)) {
                            $this->newName .= ".$extension";
                        }
                    } while (in_array($this->newName, $existing));
                }
            }
        }
        protected function checkType($file) {
            if (in_array($file['type'], $this->permitted)) {
                return true;
            } else {
                if (!empty($file['type'])) {
                    $this->messages[] = $file['name'] . ' is not permitted type of file.';
                }
                return false;
            }
        }
        public function allowAllTypes($suffix = true) {
            $this->typeCheckingOn = false;
            if (!$suffix) {
                $this->suffix = ''; // empty string
            }
        }
        protected function checkFile($file) {
            $accept = true;
            if ($file['error'] != 0) {
                $this->getErrorMessage($file);
                // stop checking if no file submitted
                if ($file['error'] == 4) {
                    return false;
                } else {
                    $accept = false;
                }
            }
            if (!$this->checkSize($file)) {
                $accept = false;
            }
            if ($this->typeCheckingOn) {
                if (!$this->checkType($file)) {
                    $accept = false;
                }
            }
            if ($accept) {
                $this->checkName($file);

            }
            return $accept;
        }
        protected function checkSize($file) {
            if ($file['error'] == 1 || $file['error'] == 2 ) {
                return false;
            } elseif ($file['size'] == 0) {
                $this->messages[] = $file['name'] . ' is an empty file.';
                return false;
            } elseif ($file['size'] > $this->max) {
                $this->messages[] = $file['name'] . ' exceeds the maximum size for a file (' . $this->getMaxSize() . ').';
                return false;
            } else {
                return true;
            }
        }


        protected function moveFile($file) {
            $filename = isset($this->newName) ? $this->newName : $file['name'];
            $success = move_uploaded_file($file['tmp_name'],
            $this->destination . $filename);
            if ($success) {
                // add a message only if the original image is not deleted
                if (!$this->deleteOriginal) {
                    $result = $file['name'] . ' was uploaded successfully';
                    if (!is_null($this->newName)) {
                        $result .= ', and was renamed ' . $this->newName;
                    }
                    $this->messages[] = $result;
                }
            } else {
                $this->messages[] = 'Could not upload ' . $file['name'];
            }
        }


        public function getMessages() {
            return $this->messages;
        }

        protected function getErrorMessage($file) {
            switch($file['error']) {
                case 1:
                case 2:
                    $this->messages[] = $file['name'] . ' is too big: (max: ' .
                    $this->getMaxSize() . ').';
                    break;
                case 3:
                    $this->messages[] = $file['name'] . ' was only partially uploaded.';
                    break;
                case 4:
                    $this->messages[] = 'No file submitted.';
                    break;
                default:
                    $this->messages[] = 'Sorry, there was a problem uploading ' . $file['name'];
                    break;
            }
        }
        public function getMaxSize() {
            return number_format($this->max/1024, 1) . ' KB';
        }
        public function setMaxSize($num) {
            if (is_numeric($num) && $num > 0) {
                $this->max = (int) $num;
            }
        }
        protected function checkName($file) {
            $this->newName = null;
            $nospaces = str_replace(' ', '_', $file['name']);
            if ($nospaces != $file['name']) {
                $this->newName = $nospaces;
                if (!$this->typeCheckingOn && !empty($this->suffix)) {
                    if (in_array($extension, $this->notTrusted) || empty($extension)) {
                        $this->newName = $nospaces . $this->suffix;
                    }
                }
            }

        }

    }


?>
