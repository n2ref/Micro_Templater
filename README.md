Micro Templater
===============

Simple but powerful PHP template engine. <br>
Just one file. <br>
Have a look how it easy to use. <br>

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
    'home'    => 'Home',
    'gallery' => 'Gallery',
    'help'    => 'Help'
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
    <li><a href="?view=home">Home</a></li>
    <li><a href="?view=gallery">Gallery</a></li>
    <li><a href="?view=help">Help</a></li>
</ul>
```


##### Fill drop down

###### Example # 1 
```php
$html = '
    <h1>Title</h1>
    <form>
        <div class="form-group">
            <label for="year">Year</label>
            <select id="year" name="year" class="form-control"></select>
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
';

$tpl = new Micro_Templater();
$tpl->setTemplate($html);


$years = array(
    2000 => '2000',
    2005 => '2005',
    2010 => '2010',
    2015 => '2015'
);
$tpl->fillDropDown('select#year', $years, 2015);


echo $tpl->render();
```
Result will look like:

```html
    <h1>Title</h1>
    <form>
        <div class="form-group">
            <label for="year">Year</label>
            <select id="year" name="year" class="form-control">
                <option value="2000">2000</option>
                <option value="2005">2005</option>
                <option value="2010">2010</option>
                <option value="2015" selected="selected">2015</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
```


##### Attributes

###### Example # 1 
```php
$html = '
    <h1>Title</h1>
    <form>
        <div class="form-group">
            <label for="day">Day</label>
            <input id="day" name="day" class="form-control"/>
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
';

$tpl = new Micro_Templater();
$tpl->setTemplate($html);

$tpl->setAttr('input#day', 'required', 'required');
$tpl->setAppendAttr('input#day', 'class', ' xxx');
$tpl->setAttribs('form', array(
    'action'   => "index.php",
    'method'   => "get",
    'onsubmit' => "return this.day.value != ''",
));

echo $tpl->render();
```
Result will look like:

```html
    <h1>Title</h1>
    <form action="index.php" method="get" onsubmit="return this.day.value != ''">
        <div class="form-group">
            <label for="day">Day</label>
            <input id="day" name="day" class="form-control xxx" required="required"/>
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
```
