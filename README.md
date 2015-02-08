Micro Templater
===============

Simple and strong templater

#### Examples

##### Veriables
###### Example # 1 Using the method assign

```php
$html = '
    Title
    <ul>
        <li class="var1">
            [var2]
        </li>
    </ul>
';
 
$tpl = new Micro_Templater();
$tpl->setTemplate($html);
 
$tpl->assign('var1',   'foo');
$tpl->assign('[var2]', 'bar');
 
echo $tpl->render();
```
Result will look like:

```html
Title
<ul>
    <li class="foo">
        bar
    </li>
</ul>
```

##### Blocks

###### Example # 1 A clear indication of the block
```php
$html = '
    Title
    <!-- BEGIN error -->
    <div class="error">Exemple 1: error message!</div>
    <!-- END error -->
';
 
$tpl = new Micro_Templater();
$tpl->setTemplate($html);
 
$tpl->touchBlock('error');
 
echo $tpl->render();
```
Result will look like:

```html
Title
<div class="error">Exemple 1: error message!</div>
```

###### Example # 2 Implicit indication block
```php
$html = '
    Title
    <!-- BEGIN error -->
    <div class="error">[message]</div>
    <!-- END error -->
';
 
$tpl = new Micro_Templater();
$tpl->setTemplate($html);
 
$tpl->error->assign('[message]', 'Exemple 2: error message!');
 
echo $tpl->render();
```
Result will look like:

```html
Title
<div class="error">Exemple 2: error message!</div>
```

##### Loops

###### Example # 1 A list of menu
```php
$html = '
    Title
    <ul>
        <!-- BEGIN menu -->
        <li><a href="[URL]">[TITLE]</a></li>
        <!-- END menu -->
    </ul>
';
 
$menu = array(
    'home'    => 'Главная',
    'gallery' => 'Галерея',
    'help'    => 'Помощь'
);
 
$tpl = new Micro_Templater();
$tpl->setTemplate($html);
 
foreach ($menu as $page_name=>$title) {
    $tpl->menu->assign('[URL]',  '?view=' . $page_name);
    $tpl->menu->assign('[TITLE]', $title);
    $tpl->menu->reassign();
}
 
echo $tpl->render();
```
Result will look like:

```html
Title
<ul>
    <li><a href="?view=home">Главная</a></li>
    <li><a href="?view=gallery">Галерея</a></li>
    <li><a href="?view=help">Помощь</a></li>
</ul>
```
