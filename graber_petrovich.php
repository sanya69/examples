<?

/**
 * Грабер данных сайта Петрович.ру
 */ 

setlocale(LC_ALL, 'ru_RU.UTF8');
header('Content-Type: text/html; charset=UTF-8');

class Petro 
{
    // Дерево категорий с подкатегориями
    private $categories_tree = array();
    // Массив категорий с товарами в них
    private $products_by_categories = array();
    // Массив всех товаров
    private $products = array();
    // Массив свойств со значениями и товарами у значений
    private $properties = array();
    // ID категорий второго уровня для парсинга всех свойств
    private $second_level_categories_ids = array();
    
    public function parse() 
    {
        $start = microtime(true);
        
        // создаёт дерево категорий и попутно делает все остальные действия
        $this->doCategoriesTree();
        
        //$this->pre($this->categories_tree);
        //$this->exportCategoriesTree($this->categories_tree);
        //$this->pre($this->products_by_categories);
        //$this->pre($this->products);
        //$this->pre($this->properties);
        
        $runtime = round(microtime(true) - $start, 3);
        echo $runtime;
    }
    
    private function doCategoriesTree() 
    {
        $catalog_html = $this->getCatalogHtml();
        if (!$catalog_html) {
            return;
        }
        
        $pattern = '!class="main__catalog-categoty">(.*?)</ul>[\s]+</div>[\s]+</div>!is';
        preg_match_all($pattern, $catalog_html, $main_categories_content);
        
        $pattern2 = '!main__catalog-title">(.*?)<!is';
        preg_match_all($pattern2, $catalog_html, $main_categories_titles);
        
        foreach ($main_categories_content[1] as $key => $match) {            
            $pattern = '!a href="([0-9]+)/"!is';
            preg_match($pattern, $match, $_matches);
            $parent_category_id = $_matches[1];
            
            $pattern = '!a href="([0-9]+)"!is';
            preg_match_all($pattern, $match, $_matches);
            $child_categories_ids = $_matches[1];
            
            $pattern = '!class="main__catalog-item">(.*?)<!is';
            preg_match_all($pattern, $match, $child_categories_titles);
            
            $child_categories = array();
            foreach ($child_categories_ids as $key2 => $child_category_id) {
                $child_category = array(
                    'catid'     => $child_category_id,
                    'catname'   => trim($child_categories_titles[1][$key2]),
                    'level'     => 2,
                    'parent_id' => $parent_category_id,
                    'childs'    => array()
                );
                array_push($child_categories, $child_category);
                
                // запоминаем категории второго уровня
                array_push($this->second_level_categories_ids, $child_category_id);
            }
            
            $tree_item = array(
                'catid'     => $parent_category_id,
                'catname'   => trim($main_categories_titles[1][$key]),
                'level'     => 1,
                'parent_id' => 0,
                'childs'    => $child_categories
            );
            
            array_push($this->categories_tree, $tree_item);
        }
        
        foreach ($this->categories_tree as &$tree_item) {
            $tree_item['childs'] = $this->getChildTree($tree_item['childs']);
        }
        unset($tree_item);
    }
    
    private function getChildTree($childs) 
    {
        if (!$childs) {
            return;
        }
        
        foreach ($childs as &$child) {
            $child['childs'] = $this->getChilds($child['catid'], $child['level'] + 1);
        }
        unset($child);
        
        return $childs;
    }
    
    private function getChilds($parent_category_id, $level) 
    {
        $category_html = $this->getCategoryHtml($parent_category_id);
        
        $this->getCategoryProductsIds($parent_category_id, $category_html);
        
        if (in_array($parent_category_id, $this->second_level_categories_ids)) {
            $this->getProperties($category_html);
        }
        
        $pattern = '!data-href-url="/catalog/([0-9]+)/" class!is';
        preg_match_all($pattern, $category_html, $child_categories_ids);
        
        $pattern2 = '!alt="Каталог: (.*?)" title!is';
        preg_match_all($pattern2, $category_html, $child_categories_titles);
        
        $child_categories = array();
        
        if (!empty($child_categories_ids[1])) {
            foreach ($child_categories_ids[1] as $key => $child_category_id) {
                $child_category = array(
                    'catid'     => $child_category_id,
                    'catname'   => trim($child_categories_titles[1][$key]),
                    'level'     => $level,
                    'parent_id' => $parent_category_id,
                    'childs'    => array(),
                );
                
                array_push($child_categories, $child_category);
            }
            
            $child_categories = $this->getChildTree($child_categories);
        }
        
        return $child_categories;
    }
    
    private function getCategoryProductsIds($category_id, $category_html) 
    {
        $data = $this->getCategoryData($category_html);        
        $products_ids = array();
        
        foreach ($data['products'] as $product) {
            array_push($products_ids, $product['externalId']);
            
            if (!isset($this->products[$product['externalId']])) {
                $this->products[$product['externalId']] = $product;
            }
        }
        
        $this->products_by_categories[$category_id] = $products_ids;
    }
    
    private function getProperties($category_html) 
    {
        $data = $this->getCategoryData($category_html); 
        
        foreach ($data['filters'] as $filter_name => $filter_data) {
            if (in_array($filter_name, array('price','hasBonus','action'))) {
                continue;
            }
            
            if (!isset($this->properties[$filter_name])) {
                $this->properties[$filter_name] = array(
                    'title'  => $filter_data['title'],
                    'values' => array(),
                );
            }
            
            if (!empty($filter_data['value'])) {
                foreach ($filter_data['value'] as $filter_value) {
                    if (!isset($filter_value['id'])) {
                        echo $filter_name.': '.$filter_value['title'];
                        $this->pre($data);
                        exit;
                    }
                
                    if (!isset($this->properties[$filter_name]['values'][$filter_value['id']])) {
                        $this->properties[$filter_name]['values'][$filter_value['id']] = array(
                            'title'    => $filter_value['title'],
                            'products' => array(),
                        );
                    }
                    
                    if (!empty($filter_value['products'])) {
                        foreach ($filter_value['products'] as $_product_id) {
                            if (!in_array($_product_id, $this->properties[$filter_name]['values'][$filter_value['id']]['products'])) {
                                array_push($this->properties[$filter_name]['values'][$filter_value['id']]['products'], $_product_id);
                            }
                        }
                    }
                }
            }
        }
    }
    
    private function exportCategoriesTree($categories_tree) 
    {
        foreach ($categories_tree as $item) {
            echo $item['catid'].' '.$item['catname'].' level='.$item['level'].' parent_id='.$item['parent_id'].'<br>';
            if (!empty($item['childs'])) {
                $this->exportCategoriesTree($item['childs']);
            }
        }
    }
    
    private function getCategoryHtml($category_id) 
    {        
        $category_url = 'http://moscow.petrovich.ru/catalog/'.$category_id.'/';
        $category_html = file_get_contents($category_url);
        
        return $category_html;
    }
        
    private function getCatalogHtml() 
    {
        $catalog_url = 'http://moscow.petrovich.ru/catalog/';
        $catalog_html = file_get_contents($catalog_url);
        
        return $catalog_html;
    }
    
    private function getCategoryData($category_html)
    {
        $start_pos = strpos($category_html, 'catalogFullData =');
        $category_html = substr($category_html, $start_pos);
        $category_html = strip_tags($category_html);
        $json = trim(str_replace('catalogFullData = ', '', $category_html));
        unset($category_html);
        
        if (!strlen($json)) {
            echo $category_id.': no json<br>'; exit;
        }
        
        $data = json_decode($json, 1);
        unset($json);
        
        return $data;
    }
    
    private function pre($variable)
    {
        echo'<pre>'.print_r($variable,1).'</pre>';
    }
}

$petro = new Petro();
$petro->parse();

?>