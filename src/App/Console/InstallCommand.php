<?php

namespace CoreCMF\Corecmf\App\Console;

use Illuminate\Console\Command;
use CoreCMF\Core\Support\Commands\Install;

use CoreCMF\Core\App\Models\User;
use CoreCMF\Core\App\Models\Role;

class InstallCommand extends Command
{
    /**
     *  install class.
     * @var object
     */
    protected $install;
    protected $isDataSetted = false;
    /**
     * @var \Illuminate\Support\Collection
     */
    protected $data;
    /**
     * The name and signature of the console command.
     *
     * @var string
     * @translator laravelacademy.org
     */
    protected $signature = 'corecmf:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'corecmf install';

    public function __construct(Install $install)
    {
        parent::__construct();
        $this->install = $install;
        $this->data = collect();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info($this->install->publish('corecmf'));
        if (!$this->isDataSetted) {
          $this->setDataCommands();
        }
        $this->setEnv();
        $this->info('Set .env Success');
        $this->installModule();
        $this->info('Install Module Success');
        $this->setAdmin();
        $this->info('Set Admin Account '.$this->data->get('admin_account'));
    }
    public function setEnv()
    {
        $this->install->setEnv('APP_URL', 'http://'.$this->data->get('weburl'));
        $this->install->setEnv('DB_CONNECTION', $this->data->get('driver'));
        $this->install->setEnv('DB_HOST', $this->data->get('database_host'));
        $this->install->setEnv('DB_PORT', $this->data->get('database_port'));
        $this->install->setEnv('DB_DATABASE', $this->data->get('database'));
        $this->install->setEnv('DB_USERNAME', $this->data->get('database_username'));
        $this->install->setEnv('DB_PASSWORD', $this->data->get('database_password'));
        return true;
    }
    public function setAdmin()
    {
        $userModel = new User();
        $user = $userModel->create([
            'name' 	      => $this->data->get('admin_account'),
            'email' 	    => $this->data->get('admin_email'),
            'mobile' 	    => $this->data->get('admin_mobile'),
            'password' 	  => $this->data->get('admin_password')
        ]);
        $user->userInfos()->create([
            'user_id'   => $user->id,
            'avatar'    => 1,
            'integral'  => 0,
            'money'     => 0,
        ]);//插入关联数据库userInfos
        $roles = Role::where('name', 'admin')->first();//获取admin角色id
        $user->roles()->attach($roles->id);//多对多关联 关联用户id
        touch(storage_path() . DIRECTORY_SEPARATOR . 'installed');//锁定安装文件
        return true;
    }
    public function installModule()
    {
        $this->install->installModule('core');
        $this->install->installModule('admin');
    }
    public function setDataCommands()
    {
        $this->data->put('driver', $this->ask('数据库引擎(mysql/pgsql/sqlite)：'));
        if (in_array($this->data->get('driver'), [
            'mysql',
            'pgsql',
        ])) {
            $this->data->put('database_host', $this->ask('数据库服务器：'));
            $this->data->put('database_port', $this->ask('数据库服务器端口(3306/5432)：'));
            $this->data->put('database', $this->ask('数据库名：'));
            $this->data->put('database_username', $this->ask('数据库用户名：'));
            $this->data->put('database_password', $this->ask('数据库密码：'));
        }
        // $this->data->put('database_prefix', $this->ask('数据库表前缀：'));
        $this->data->put('admin_account', $this->ask('管理员帐号：'));
        $this->data->put('admin_password', $this->ask('管理员密码：'));
        $this->data->put('admin_email', $this->ask('电子邮箱：'));
        $this->data->put('admin_mobile', $this->ask('管理员手机'));
        $this->data->put('weburl', $this->ask('网站地址：'));
        $this->info('所填写的信息是：');
        $this->info('数据库引擎：' . $this->data->get('driver'));
        if (in_array($this->data->get('driver'), [
            'mysql',
            'pgsql',
        ])) {
            $this->info('数据库服务器：' . $this->data->get('database_host'));
            $this->info('数据库服务器端口：' . $this->data->get('database_port'));
            $this->info('数据库名：' . $this->data->get('database'));
            $this->info('数据库用户名：' . $this->data->get('database_username'));
            $this->info('数据库密码：' . $this->data->get('database_password'));
        }
        // $this->info('数据库表前缀：' . $this->data->get('database_prefix'));
        $this->info('管理员帐号：' . $this->data->get('admin_account'));
        $this->info('管理员密码：' . $this->data->get('admin_password'));
        $this->info('电子邮箱：' . $this->data->get('admin_email'));
        $this->info('管理员手机：' . $this->data->get('admin_mobile'));
        $this->info('网站地址：' . $this->data->get('weburl'));
        $this->isDataSetted = true;
    }
    /**
     * Get data controller.
     *
     * @param array $data
     */
    public function setSqlController(array $data)
    {
        $this->data->put('driver', $data['database_engine']);
        $this->data->put('database_host', $data['database_host']);
        $this->data->put('database', $data['database_name']);
        $this->data->put('database_username', $data['database_username']);
        $this->data->put('database_password', $data['database_password']);
        $this->data->put('database_port', $data['database_port']);
        $this->data->put('weburl', $data['weburl']);
        $this->isDataSetted = true;
    }
    public function setAdminController(array $data)
    {
        $this->data->put('admin_account', $data['admin_account']);
        $this->data->put('admin_password', $data['admin_password']);
        $this->data->put('admin_email', $data['admin_email']);
        $this->data->put('admin_mobile', $data['admin_mobile']);
        $this->isDataSetted = true;
    }
}
