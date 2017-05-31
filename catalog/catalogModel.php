<?php

class catalogModel
{
    // выбранные значения свойств. заполняются из URL. массив связей [property_order] = value_alias
    private $selected_values = array();
    // выбранные значения свойств. заполняются из URL. массив values_aliases
    private $selected_values_hash = array();
    
    private $count_founded = 0;
    private $current_page = 1;
    private $count_on_page = 20;
    
    public function __construct()
    {
        $this->parsePath();
    }
    
    /* 
     * Парсим параметры URL-запроса
     */
    private function parsePath()
    {
        if (empty($_GET['path'])) {
            return;
        }
        
        $path = explode('/', $_GET['path']);
        
        // номер страницы
        $last_segment = $path[count($path) - 1];
        if (is_numeric($last_segment)) {
            $last_segment = (int)$last_segment;
            if ($last_segment < 1) {
                exit('Bad page number');
            }
            
            $this->current_page = $last_segment;
            array_pop($path);
        }
        
        if ($path && !$this->chechPath($path)) {
            exit('Bad path');
        }
    }
    
    /* 
     * Проверяем параметры URL-запроса 
     * на сущестование сегментов и на корректность порядка значений 
     * и заполняем свойство selected_values
     */
    private function chechPath($path = array())
    {
        if (!$path) {
            return 0;
        }
        
        foreach ($path as $alias) {
            $value_order = $this->getValueOrder($alias);
            if (!$value_order) {
                // bad alias
                return 0;
            }
            
            if ($this->selected_values) {
                end($this->selected_values);
                if ($value_order <= key($this->selected_values)) {
                    // bad properties order
                    return 0;
                }
            }
            
            $this->selected_values[$value_order] = $alias;
            $this->selected_values_hash[$alias] = '';
        }
        
        return 1;
    }
    
    public function getProducts()
    {
        $products = array();
        
        $where = '';
        if ($this->selected_values) {
            $filtered_products_ids = $this->getValuesIntersect($this->selected_values);
            
            if (!$filtered_products_ids) {
                return $products;
            }
            
            $where = ' WHERE `id` IN ('.implode(',', $filtered_products_ids).') ';
        }
        
        $count_sql = 'SELECT COUNT(`id`) FROM `products`'.$where;
        $this->count_founded = $this->db->query($count_sql)->fetchColumn();
        
        if (!$this->count_founded) {
            if ($this->selected_values) {
                return $products;
            } else {
                exit('No any products');
            }
        }
        
        $limit = ($this->current_page - 1).','.$this->count_on_page;
        $sql = 'SELECT `name` FROM `products` '.$where.' ORDER BY `id` ASC LIMIT '.$limit;
        $products = $this->db->query($sql)->fetchAll(PDO::FETCH_COLUMN);
        
        return $products;
    }
    
    public function getFilters()
    {
        if (!$this->properties || !$this->properties_values) {
            return;
        }
        
        $properties_values = array();
        
        foreach ($this->properties_values as $item) {
            if (!isset($properties_values[$item['property_id']])) {
                $properties_values[$item['property_id']] = array();
            }
            
            $active = $this->getValueActive($item['alias']);
            
            if ($active) {
                $url = $this->getValueUrl($item['alias']);
                $selected = $this->getValueSelected($item['alias']);
            } else {
                // неактивный не может быть выбран
                $url = '';
                $selected = 0;
            }
            
            $properties_values[$item['property_id']][] = array(
                'name'     => $item['name'],
                'url'      => $url,
                'active'   => $active,
                'selected' => $selected
            );
        }
        
        $filters = array();
        
        foreach ($this->properties as $property) {
            $values = isset($properties_values[$property['id']]) ? $properties_values[$property['id']] : array();
            
            $filters[] = array(
                'name'   => $property['name'],
                'values' => $values,
            );
        }
        unset($values, $properties_values);
        
        return $filters;
    }
    
    public function getPagination()
    {
        $count_pages = $this->count_founded ? ceil($this->count_founded / $this->count_on_page) : 0;
        if ($count_pages < 2) {
            return;
        }
        
        $offset = 3;
        $limit_pages = 10;
        $base_url = $this->getBaseUrl($this->selected_values);
        
        $items = array();
        
        if ($count_pages > $limit_pages) {
            $elements = array();
            
            if ($this->current_page == 1) {
                // first +3 ... last
                
                $end = 1 + $offset;
                for ($i = 1; $i <= $end; $i++) {
                    $elements[] = $i;
                }
                
                array_push($elements, '...', $count_pages);
            } else if ($this->current_page == $count_pages) {
                // first ... -3 last
                
                array_push($elements, 1, '...');
                
                $start = $count_pages - $offset;
                for ($i = $start; $i <= $count_pages; $i++) {
                    $elements[] = $i;
                }
            } else {
                // first -3 page + 3 ... last
                
                $start = $this->current_page - $offset;
                
                if (($start - 1) <= 2) {
                    $start = 1;
                } else {
                    array_push($elements, 1, '...');
                }
                
                $need_end_ellipsis = 1;
                $end = $this->current_page + $offset;

                if (($count_pages - $end) <= 2) {
                    $end = $count_pages;
                    $need_end_ellipsis = 0;
                }
                
                for ($i = $start; $i <= $end; $i++) {
                    $elements[] = $i;
                }
                
                if ($need_end_ellipsis) {
                    array_push($elements, '...', $count_pages);
                }
            }
            
            foreach ($elements as $element) {
                if (is_numeric($element)) {
                    $items[] = array(
                        'number' => $element,
                        'url'    => $this->getPageUrl($base_url, $element),
                    );
                } else {
                    $items[] = $element;
                }
            }
        } else {
            for ($page_number = 1; $page_number <= $count_pages; $page_number++) {
                $items[] = array(
                    'number' => $page_number,
                    'url'    => $this->getPageUrl($base_url, $page_number),
                );
            }
        }
        
        return array(
            'items'         => $items,
            'current_page'  => $this->current_page,
            'count_founded' => $this->count_founded
        );
    }
    
    private function getPageUrl($base_url = '', $page_number = 0)
    {
        if (!$base_url || !$page_number) {
            return;
        }
        
        return $base_url.($page_number > 1 ? $page_number.'/' : '');
    }
    
    private function getValueOrder($alias = '')
    {
        if (!$alias) {
            return;
        }
        
        if (!$this->values_orders) {
            return;
        }
        
        return isset($this->values_orders[$alias]) ? $this->values_orders[$alias] : 0;
    }
    
    private function getValueActive($alias = '')
    {
        if (!$alias) {
            return;
        }
        
        if (!$this->getProductsidsByValueAlias($alias)) {
            // с этим значением вообще нет товаров - значение неактивно
            return 0;
        }
        
        if (!$this->selected_values) {
            // нет выбранных значений вообще - значение активно
            return 1;
        }
        
        // с этим значением есть товары и есть уже выбранные значения
        
        $selected = $this->getValueSelected($alias);
        
        if ($selected && count($this->selected_values) == 1) {
            // выбрано только это значение - значение активно
            return 1;
        }
        
        $value_order = $this->getValueOrder($alias);
        if (!$value_order) {
            return;
        }
        
        $selected_values = $this->selected_values;
        $selected_values[$value_order] = $alias;
        
        return $this->getValuesIntersect($selected_values) ? 1 : 0;
    }
    
    private function getProductsidsByValueAlias($alias = '')
    {
        if (!$alias) {
            return;
        }
        
        if (!$this->values_productsids) {
            return;
        }
        
        return isset($this->values_productsids[$alias]) ? $this->values_productsids[$alias] : array();
    }
    
    private function getValueUrl($alias = '')
    {
        if (!$alias) {
            return;
        }
        
        if (!$this->selected_values) {
            $segments = array($alias);
        } else {
            $value_order = $this->getValueOrder($alias);
            if (!$value_order) {
                return;
            }
            
            $segments = $this->selected_values;
            
            if (isset($segments[$value_order]) && $segments[$value_order] == $alias) {
                unset($segments[$value_order]);
            } else {
                $segments[$value_order] = $alias;
            }
        }
        
        ksort($segments);
        
        return $this->getBaseUrl($segments);
    }
    
    private function getBaseUrl($segments = array()) {
        $base_path = '/catalog/';
        
        if (!$segments) {
            return $base_path;
        }
        
        return $base_path.implode('/', $segments).'/';
    }
    
    private function getValueSelected($alias = '')
    {
        if (!$alias) {
            return;
        }
        
        return isset($this->selected_values_hash[$alias]) ? 1 : 0;
    }
    
    private function getValuesIntersect($values = array())
    {
        if (!$values) {
            return;
        }
        
        $intersect = array();
        
        foreach ($values as $value) {
            $products_ids = $this->getProductsidsByValueAlias($value);
            if (!$products_ids) {
                return;
            }
            
            if (!$intersect) {
                $intersect = $products_ids;
            } else {
                $intersect = array_intersect($intersect, $products_ids);
                if (!$intersect) {
                    return;
                }
            }
        }
        
        return $intersect;
    }
    
    // Ниже идут вспомогаельные методы
    
    /*
     * Magic getter
     */
    public function __get($name = '')
    {
        if (!$name) {
            return;
        }
        
        $segments = explode('_', $name);
        $method = 'get';
        foreach ($segments as $segment) {
            $method .= ucfirst($segment);
        }
        
        if (!method_exists(__CLASS__, $method)) {
            return;
        }
        
        $this->{$name} = $this->$method();
        
        return $this->{$name};
    }
    
    private function getValuesProductsids()
    {
        if (!$this->values_products || !$this->values_aliases) {
            return;
        }
        
        $values_productsids = array();
        
        foreach ($this->values_products as $item) {
            if (empty($this->values_aliases[$item['value_id']])) {
                continue;
            }
            $value_alias = $this->values_aliases[$item['value_id']];
            
            if (!isset($values_productsids[$value_alias])) {
                $values_productsids[$value_alias] = array();
            }
            
            $values_productsids[$value_alias][] = $item['product_id'];
        }
        
        return $values_productsids;
    }
    
    private function getValuesAliases()
    {
        if (!$this->properties_values) {
            return;
        }
        
        $values_aliases = array();
        
        foreach ($this->properties_values as $value) {
            $values_aliases[$value['id']] = $value['alias'];
        }
        
        return $values_aliases;
    }
    
    private function getValuesOrders()
    {
        if (!$this->properties || !$this->properties_values) {
            return;
        }
        
        $properties_orders = array();
        foreach ($this->properties as $property) {
            $properties_orders[$property['id']] = $property['order'];
        }
        
        $values_orders = array();
        foreach ($this->properties_values as $value) {
            if (!isset($properties_orders[$value['property_id']])) {
                continue;
            }
            
            $values_orders[$value['alias']] = $properties_orders[$value['property_id']];
        }
        unset($properties_orders);
        
        return $values_orders;
    }
    
    private function getValuesProducts()
    {
        $sql = 'SELECT `product_id`, `value_id` FROM `properties_values_products`';
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getPropertiesValues()
    {
        $sql = 'SELECT `id`, `property_id`, `name`, `alias` FROM `properties_values` ORDER BY `property_id`, `order`';
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getProperties()
    {
        $sql = 'SELECT `id`, `name`, `order` FROM `properties` ORDER BY `order`';
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getDb()
    {
        require('./db_connect.php');
        return $db_connect;
    }
    
    private static function pre($var) {
        echo'<pre>'.print_r($var,1).'</pre>';exit;
    }
}