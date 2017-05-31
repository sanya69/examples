<html>
<head>
    <meta charset="utf-8">
    <title>Каталог пирожков</title>
    <link rel="stylesheet" href="/catalog/catalog.css">
</head>
<body>
    <? if ($filters): ?>
    <div id="filters">
    <? foreach ($filters as $filter): ?>
        <div class="filter">
            <div class="filter_name"><?= $filter['name'] ?>:</div>
            <div class="filter_values">
            <? foreach ($filter['values'] as $filter_value): ?>
                <div class="filter_value<?= ($filter_value['selected'] ? ' selected' : '').(!$filter_value['active'] ? ' inactive' : '') ?>">
                <? if ($filter_value['active']): ?>
                    <?= '<a href="'.$filter_value['url'].'">'.$filter_value['name'].'</a>' ?>
                <? else: ?>
                    <?= $filter_value['name'] ?>
                <? endif; ?>
                </div>
            <? endforeach; ?>
            </div>
        </div>
    <? endforeach; ?>
    </div>
    <? endif; ?>
    <br>
    <br>
    <? if ($products): ?>
    <div id="products">
    <? foreach ($products as $product_name): ?>
        <div><?= $product_name ?></div>
    <? endforeach; ?>
    </div>
    <? endif; ?>
    <br>
    <br>
    <div>Найдено: <?= $pagination['count_founded'] ?> шт</div>
    <br>
    <? if ($pagination && !empty($pagination['items'])): ?>
    <div id="pagination">
    <? foreach ($pagination['items'] as $item): ?>
        <? if (is_array($item)): ?>
        <div class="page_item<?= ($item['number'] == $pagination['current_page'] ? ' current' : '') ?>">
            <a href="<?= $item['url'] ?>"><?= $item['number'] ?></a>
        </div>
        <? else: ?>
        <div class="page_item space">...</div>
        <? endif; ?>
    <? endforeach; ?>
    </div>
    <? endif; ?>
</body>
</html>