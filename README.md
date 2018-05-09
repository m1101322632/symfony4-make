# symfony4-make
    supply similar method about make for symfony4, but you can change the dictory where your file created in.
    在symfonyt4中生成控制器和crud时，提供生成目录选项控制生成文件所在目录。生成的文件里的命名空间、路由、模板路径都会被同步修改。

## 环境：
     symfony 4.0
     php 7.2
## 安装示例：
    下载文件，放到你项目目录下src/Command中
    
## 使用示例：

### 1.生成控制器
    php bin\console mingche:controller HeiBiDa backend
    生成文件目录结构为：
    src\Controller\backend\HeiBiDaController.php
    templates\backend\hei_bi_da\
       -index.html.twig
### 2.生成curd       
    php bin\console mingche:crud HeiBiDa backend
    生成文件目录结构为：
    src\Controller\backend\HeiBiDaController.php
    templates\backend\hei_bi_da\
       -index.html.twig
       -show.html.twig
       -edit.html.twig
       -new.html.twig
       ...
       
 
