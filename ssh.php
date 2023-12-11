<?php

class sshInfo
{

    const SSH_FILE = 'id_rsa';
    const SSH_FILE_PUB = 'id_rsa.pub';

    private $sshFolder = '';
    private $argv = [];
    private $dirs = [];

    private $execute = [
        'list' => 'List of current ssh keys',
        'whoami' => 'Current ssh user',
        'change' => 'Change current ssh keys to selected ssh key',
        'help'  => 'Current help'
    ];

    public function __construct($arg)
    {
        $this->sshFolder = __DIR__;
        $this->argv = $arg;
        $this->run();
    }

    private function getListDir()
    {
        $all = scandir($this->sshFolder);
        $i = 1;
        foreach ($all as $dir) {
            if ($dir == '.' || $dir == '..') {
                continue;
            }
            if (is_dir($this->sshFolder . '/' . $dir)) {
                if (file_exists($this->sshFolder . '/' . $dir . '/' . self::SSH_FILE) && file_exists($this->sshFolder . '/' . $dir . '/' . self::SSH_FILE_PUB)) {
                    $this->dirs[$i] = $dir;
                    $i++;
                }
            }
        }
    }

    private function actionList()
    {
        $this->getListDir();

        foreach ($this->dirs as $key => $dir) {
            $this->out(' ' . $key . '. ' . $dir, 's');
        }
    }

    private function copyFileFromDir($id)
    {
        $result = true;
        if (file_exists($this->sshFolder . '/' . self::SSH_FILE)) {
            $result = $result && unlink($this->sshFolder . '/' . self::SSH_FILE);
        }
        $result = $result && copy($this->sshFolder . '/' . $this->dirs[$id] . '/' . self::SSH_FILE, $this->sshFolder . '/' . self::SSH_FILE);

        if (!$result) {
            $this->out('Apply ssh file is incorrect');
            exit;
        }
    }

    private function copyFilePubFromDir($id)
    {
        $result = true;
        if (file_exists($this->sshFolder . '/' . self::SSH_FILE_PUB)) {
            $result = $result && unlink($this->sshFolder . '/' . self::SSH_FILE_PUB);
        }
        $result = $result && copy($this->sshFolder . '/' . $this->dirs[$id] . '/' . self::SSH_FILE_PUB, $this->sshFolder . '/' . self::SSH_FILE_PUB);

        if (!$result) {
            $this->out('Apply ssh PUB file is incorrect');
            exit;
        }
    }

    private function runChangeFile($id)
    {
        $changeFile = $this->sshFolder . '/' . $this->dirs[$id] . '/change';
        if (file_exists($changeFile)) {
            $commands = file($changeFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (count($commands) == 0) {
                return;
            }
            $this->out('Execute commands:', 'i');
            foreach ($commands as $command) {
                $this->out($command, 'w');
                $output = '';
                $result = exec($command, $output);
                if (!empty($output)) {
                    $this->out($output);
                }
                if (is_bool($result) && !$result) {
                    $this->out('Failed', 'e');
                }
            }
        }
    }

    private function actionChange()
    {
        $this->actionList();
        $this->out("select option: ", null, false);
        $num = fgets(STDIN);
        $num = (int)$num;

        if (!isset($this->dirs[$num])) {
            $this->out('incorrect option', 'e');
            exit;
        }

        $this->copyFileFromDir($num);
        $this->copyFilePubFromDir($num);
        $this->runChangeFile($num);

    }

    private function actionWhoami()
    {
        $readPub = file_get_contents($this->sshFolder . '/' . self::SSH_FILE_PUB);
        $sshArr = explode(' ', $readPub);
        $this->out(trim(end($sshArr)));
    }

    private function actionHelp()
    {
        $execute = $this->execute;
        ksort($execute);
        foreach ($execute as $key => $description) {
            $this->out(' ' . str_pad($key, 19, ' '), 's', false);
            $this->out($description);
        }
    }

    private function run()
    {
        $key = (isset($this->argv[1]) ? strtolower($this->argv[1]) : null);

        if (!isset($this->execute[$key])) {
            $key = 'help';
        }

        $action = 'action' . ucfirst($key);
        if (is_callable([$this, $action])) {
            call_user_func([$this, $action]);
        } else {
            $this->actionHelp();
        }
    }

    private function out($str, $type = null, $eol = true)
    {
        switch ($type) {
            case 'e': //error
                echo "\033[31m$str \033[0m" . (($eol) ? PHP_EOL : '');
                break;
            case 's': //success
                echo "\033[32m$str \033[0m" . (($eol) ? PHP_EOL : '');
                break;
            case 'w': //warning
                echo "\033[33m$str \033[0m" . (($eol) ? PHP_EOL : '');
                break;
            case 'i': //info
                echo "\033[36m$str \033[0m" . (($eol) ? PHP_EOL : '');
                break;
            default:
                echo $str . (($eol) ? PHP_EOL : '');
                break;
        }
    }
}

$sshInfo = new sshInfo($argv);
