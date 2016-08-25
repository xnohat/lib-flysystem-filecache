<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\flysystem\Adapter\Cache\Metadata;

/**
 * Description of AbstractFileStorage
 *
 * @author Christophe GARCIA <christopheg@taotesting.com>
 */
abstract class AbstractFileStorage extends AbstractStorage 
{
    /**
     * cache data in memory to improve performance
     * @var array
     */
    protected $memoryCache = [];
    
    protected $cacheExtension = '';
    
    /**
     * return file parse content or false
     * @return array|boolean
     */
    abstract protected function readFile($path);
    
    /**
     * serialyse data and write in cache file
     * @return boolean
     */
    abstract protected function writeFile($path , array $data);
    
    /**
     * get data from memory cache
     * @param string $path
     * @param string $key
     * @return mixed
     */
    protected function getFromMemory($path, $key = null) {
        if(array_key_exists($path, $this->memoryCache)) {
            return (is_null($key)?$this->memoryCache[$path]:$this->memoryCache[$path][$key]);
        }
        return false;
    }

    /**
     * save data in memory
     * @param string $path
     * @param mixed $value
     * @param string $key
     * @return \oat\flysystem\Adapter\JsonStorage
     */
    protected function setToMemory($path , $value , $key = null) {
        if(is_null($key)) {
            $this->memoryCache[$path][$key] = $value;
        } else {
            $this->memoryCache[$path] = $value;
        }
        return $this;
    }
    
    /**
     * get cache file path from original file path
     * @param string $path
     * @return string
     */
    protected function getCachePath($path) {
        
        $infos = pathinfo($path);
        return $infos['dirname'] . DIRECTORY_SEPARATOR . '.' . $infos['basename'] . '.' . $this->cacheExtension; 
        
    }
    
    /**
     * {@inheritdoc}
     */
    public function copy($path, $newpath) {
        copy($this->getCachePath($path), $this->getCachePath($newpath));
        if(array_key_exists($path, $this->memoryCache)) {
            $this->memoryCache[$newpath] = $this->memoryCache[$path];
        }
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function delete($path) {
        unlink($this->getCachePath($path));
        if(array_key_exists($path, $this->memoryCache)) {
            unset($this->memoryCache[$path]);
        }
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function rename($path, $newpath) {
        rename($this->getCachePath($path), $this->getCachePath($newpath));
        if(array_key_exists($path, $this->memoryCache)) {
            $this->memoryCache[$newpath] = $this->memoryCache[$path];
            unset($this->memoryCache[$path]);
        }
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function get($path, $key) {
        
        if(($result = $this->getFromMemory($path)) === false) {
            $cacheFile = $this->getCachePath($path);
             if(file_exists($cacheFile)) {
                $result = $this->readFile($cacheFile);
                return (array_key_exists($key, $result))?$result[$key] : false;
            }
        }
        return false;
    }
    
    /**
     * {@inheritdoc}
     */
    public function load($path) {
        if(($result = $this->getFromMemory($path)) !== false) {
            return $result;
        }
        $cacheFile = $this->getCachePath($path);
        if(file_exists($cacheFile)) {
            $data = $this->readFile($cacheFile);
            $this->setToMemory($path, $data);
            return $data;
        }
        return false;
    }
    
    /**
     * {@inheritdoc}
     */
    public function save($path, Config $data) {
        $cache = $this->parseData($data);
        $this->setToMemory($path, $cache);
        $cacheFile = $this->getCachePath($path);
        $this->writeFile($cacheFile , $cache);
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function set($path, $key, $value) {
        $this->setToMemory($path , $value , $key );
        $cacheFile = $this->getCachePath($path);
        $this->writeFile($cacheFile , $this->getFromMemory($path));
        return $this;
    }
}
