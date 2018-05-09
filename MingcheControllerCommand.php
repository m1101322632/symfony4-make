<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Console\Input\ArrayInput;
class MingcheControllerCommand extends Command
{
    protected static $defaultName = 'mingche:controller';
    private $kernal = null;

    public function __construct(?string $name = null, KernelInterface $kernel)
    {
        $this->kernal = $kernel;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setDescription('明澈：创建控制器')
            ->addArgument('controller-class', InputArgument::REQUIRED, '实体名称')
            ->addArgument('dir_name', InputArgument::REQUIRED, '移动目的地的目录名称');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $controller_class = $input->getArgument('controller-class');
        $dir_name = $input->getArgument('dir_name');
        $controler_dir = $this->kernal->getProjectDir().DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'Controller';
        $template_dir = $this->kernal->getProjectDir().DIRECTORY_SEPARATOR.'templates';
        if (!file_exists($controler_dir.DIRECTORY_SEPARATOR.$dir_name))
        {
            return $io->error('目录不存在');
        }

        if ($this->callSystemMakeController($input, $output, $controller_class) === 0 ) {
            //移动文件
            \rename($controler_dir.DIRECTORY_SEPARATOR.$controller_class."Controller.php",$controler_dir.DIRECTORY_SEPARATOR.$dir_name.DIRECTORY_SEPARATOR.$controller_class."Controller.php");
            $view_dir = preg_replace('/([A-Z])/', '_$1', $controller_class);
            $view_dir = substr(strtolower($view_dir),1);
            \rename($template_dir.DIRECTORY_SEPARATOR.$view_dir,$template_dir.DIRECTORY_SEPARATOR.$dir_name.DIRECTORY_SEPARATOR.$view_dir);
            //更新控制器路由
            $file_contents = file_get_contents($controler_dir.DIRECTORY_SEPARATOR.$dir_name.DIRECTORY_SEPARATOR.$controller_class."Controller.php");
            $file_contents = str_replace('@Route("/'.(str_replace('_', '/',$view_dir)).'', '@Route("/'.$dir_name."/".$view_dir.'', $file_contents);
            $file_contents = str_replace('namespace App\Controller;', 'namespace App\Controller\\'.$dir_name.';', $file_contents);
            $file_contents = preg_replace("/({$view_dir}\/.+?.html.twig)/",$dir_name.'/$1' , $file_contents);
            $file_contents = preg_replace("/name=\"{$view_dir}(.*?)\"\)/", 'name="'.$dir_name.'_'.$view_dir.'$1")', $file_contents);
            file_put_contents($controler_dir.DIRECTORY_SEPARATOR.$dir_name.DIRECTORY_SEPARATOR.$controller_class."Controller.php", $file_contents);

            //更新template引用模板
            $scan_dir_path = $template_dir.DIRECTORY_SEPARATOR.$dir_name.DIRECTORY_SEPARATOR.$view_dir;
            $templates = scandir($scan_dir_path);
            foreach ($templates as $lp_temp) {

                if($lp_temp =='..' || $lp_temp =='.'){
                    continue;
                }
                $content = file_get_contents($scan_dir_path.DIRECTORY_SEPARATOR.$lp_temp);
                $content = str_replace("{{ path('{$view_dir}", "{{ path('{$dir_name}_{$view_dir}", $content);
                $content = str_replace("{{ include('{$view_dir}", "{{ include('{$dir_name}/{$view_dir}", $content);
                file_put_contents($scan_dir_path.DIRECTORY_SEPARATOR.$lp_temp, $content);
            }
        }

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

    }

    private function callSystemMakeController(InputInterface $input, OutputInterface $output, $controller_class)
    {
        $command = $this->getApplication()->find('make:controller');
        $arguments = array(
            'command' => 'make:controller',
            'controller-class' => $controller_class
        );

        $greetInput = new ArrayInput($arguments);
        return  $command->run($greetInput, $output);
    }
}
