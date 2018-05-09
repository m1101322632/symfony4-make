<?php

/*
 * @desc 我的crud命令，对系统的curd命令做补充
 *  1.配置控制器中的路由
 *  2.配置控制器、模板生成的位置
 *  3.修正curd模板文件中的对twig文件的路径引用
 */
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\HttpKernel\KernelInterface;

class MingcheCrudCommand extends Command
{
    protected static $defaultName = 'mingche:crud';

    private $kernal = null;

    public function __construct(?string $name = null, KernelInterface $kernel)
    {
        $this->kernal = $kernel;
        parent::__construct($name);
    }

    protected function configure()
    {

        $this
            ->setDescription('明澈的curd命令')
            ->addArgument('entity-class', InputArgument::REQUIRED, '实体名称')
            ->addArgument('dir_name', InputArgument::REQUIRED, '移动目的地的目录名称');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $io = new SymfonyStyle($input, $output);
        $entity_class = $input->getArgument('entity-class');
        $dir_name = $input->getArgument('dir_name');
        $controler_dir = $this->kernal->getProjectDir().DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'Controller';
        $template_dir = $this->kernal->getProjectDir().DIRECTORY_SEPARATOR.'templates';
        if (!file_exists($controler_dir.DIRECTORY_SEPARATOR.$dir_name))
        {
            return $io->error('目录不存在');
        }

        if ($this->callSystemCrud($input, $output, $entity_class) === 0 ) {
            //移动文件
            \rename($controler_dir.DIRECTORY_SEPARATOR.$entity_class."Controller.php",$controler_dir.DIRECTORY_SEPARATOR.$dir_name.DIRECTORY_SEPARATOR.$entity_class."Controller.php");
            $view_dir = preg_replace('/([A-Z])/', '_$1', $entity_class);
            $view_dir = substr(strtolower($view_dir),1);
            \rename($template_dir.DIRECTORY_SEPARATOR.$view_dir,$template_dir.DIRECTORY_SEPARATOR.$dir_name.DIRECTORY_SEPARATOR.$view_dir);
            //更新控制器路由
            $file_contents = file_get_contents($controler_dir.DIRECTORY_SEPARATOR.$dir_name.DIRECTORY_SEPARATOR.$entity_class."Controller.php");
            $file_contents = str_replace('@Route("/'.(str_replace('_', '/',$view_dir)).'")', '@Route("/'.$dir_name."/".$view_dir.'")', $file_contents);
            $file_contents = str_replace('namespace App\Controller;', 'namespace App\Controller\\'.$dir_name.';', $file_contents);

            $prep_exp = array(
                'pattern' => array(
                    "/({$view_dir}\/.+?.html.twig)/",
                    "/name=\"{$view_dir}_(.+?)\"\, methods=/",
                    "/redirectToRoute\('{$view_dir}_/"
                ),

                'replace' => array(
                    $dir_name.'/$1',
                    'name="'.$dir_name.'_'.$view_dir.'_$1", methods=',
                    "redirectToRoute('{$dir_name}_{$view_dir}_",
                )
            );

            $file_contents = preg_replace($prep_exp['pattern'],$prep_exp['replace'] , $file_contents);
            file_put_contents($controler_dir.DIRECTORY_SEPARATOR.$dir_name.DIRECTORY_SEPARATOR.$entity_class."Controller.php", $file_contents);

            //更新template引用模板
            $scan_dir_path = $template_dir.DIRECTORY_SEPARATOR.$dir_name.DIRECTORY_SEPARATOR.$view_dir;
            $templates = scandir($scan_dir_path);

            $prep_exp = array(
                'pattern' => array(
                    "{{ path('{$view_dir}",
                    "/name=\"{$view_dir}_(.+?)\"\, methods=/",
                    "/redirectToRoute\('{$view_dir}_/"
                ),

                'replace' => array(
                    "{{ path('{$dir_name}_{$view_dir}",
                    'name="'.$dir_name.'_'.$view_dir.'_$1", methods=',
                    "redirectToRoute('{$dir_name}_{$view_dir}_",
                )
            );
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

        $io->success('创建成功');
    }

    private function callSystemCrud(InputInterface $input, OutputInterface $output, $entity_class)
    {
        $command = $this->getApplication()->find('make:crud');
        $arguments = array(
            'command' => 'make:crud',
            'entity-class' => $entity_class
        );

        $greetInput = new ArrayInput($arguments);
        return  $command->run($greetInput, $output);
    }
}
