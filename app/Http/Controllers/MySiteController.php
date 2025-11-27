<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\BuildNotification;
class MySiteController extends Controller
{

    public function store(Request $request)
    {
        $status = false;
        if (!$request->has('site_name') || !$request->has('folder_source_path')) {
            return response()->json(['status' => $status, 'message' => 'Site name is required.']);
        }

        $siteNameInput = $request->input('site_name');
        $folderSourcePath = $request->input('folder_source_path');
        $includePM2 = $request->input('include_pm2');
        $portPM2 = $request->input('port_pm2');
        $shContentDir = $siteNameInput . "/sh/" . $siteNameInput . "_build.sh";
        $storageSiteObject = Storage::disk('my_site_storage');
        $storageSiteObject->put($shContentDir, $this->generateShellScript($siteNameInput, $folderSourcePath, $includePM2));
        $logPathInit = $siteNameInput . "/log/" . $siteNameInput . "_first.log";
        $storageSiteObject->put($logPathInit, str()->random());
        $dataInsert = [
            'site_name'        => $siteNameInput,
            'path_log'         => $logPathInit,
            'sh_content_dir'   => $shContentDir,
            'is_generate_env'  => 1,
            'last_user_build'  => Auth ::user() -> id,
            'path_source_code' => $folderSourcePath,
            'port_pm2'         => $portPM2,
            'created_at'       => now(),
        ];
        DB::table('my_site')->insert($dataInsert);

        return redirect()->route('dashboard');
    }

    public function update(Request $request)
    {
        $updatePortPm2 = trim($request->input('port_pm2'));
        $updaterApiEndpointUrl = trim($request->input('api_endpoint_url'));
        $dataUpdate = [
            'port_pm2'         => $updatePortPm2,
            'api_endpoint_url' => $updaterApiEndpointUrl,
            'last_user_build'  => Auth ::user() -> id,
            'updated_at'       => now(),
        ];

         DB::table('my_site')->where('id', '=', $request->id)->update($dataUpdate);
        return redirect()->route('dashboard');
    }

    public function buildMySite(Request $request)
    {
        try {
            if (!$request->has('site_id')) {
                throw new \Exception('Site ID is required.');
            }
            $siteObject = DB::table('my_site')->where('id', $request->site_id)->first();
            if (!$siteObject) {
                throw new \Exception('Site not found.');
            }
            $diskStorage = Storage::disk('my_site_storage');
            $pathSH = $diskStorage->get($siteObject->sh_content_dir);//configured in the file filesystems.php
            $output = null;
            $return_var = null;
            exec("sudo /bin/bash $pathSH  2>&1", $output, $return_var);
            $update = [
                'last_build' => now(),
                'last_user_build'=> Auth::user()->id
            ];

            if ($return_var === 1) {
                $update['last_build_fail'] = now();

            } else {
                $update['last_build_success'] = now();

            }
            $update['path_log'] = $siteObject->site_name .'/log/execution_' .  $output[0] .'.log';
            // Generate env file
            if ($siteObject->is_generate_env){
                $custom = [
                    'PORT_PM2'      => $siteObject -> port_pm2,
                    'VITE_API_URL'  => $siteObject -> api_endpoint_url,
                    'VITE_WEB_NAME' => $siteObject -> site_name,
                ];
                generateEnvFile($siteObject->path_source_code, $custom);
            }
            $status = DB::table('my_site')->where('id', $request->site_id)->update($update);

            return response()->json(['status' => $status]);
        } catch (\Exception $exp) {
            return response()->json(['status' => $exp->getCode(), 'message' => $exp->getMessage()]);
        }


    }

    public function getLogLastBuildByID(Request $request): false|JsonResponse
    {
        if (!$request->has('site_id')) {
            return false;
        }
        $siteObject = DB::table('my_site')->where('id', $request->site_id)->first();
        $content = '';
        if (!empty($siteObject)) {
            $content = Storage::disk('my_site_storage')->get($siteObject->path_log);//configured in the file filesystems.php
        }
        $data = [
            'log_content' => $content,
            'site_name'   => $siteObject->site_name,
            'path_log'    => $siteObject->path_log,
        ];
        return response()->json($data);
    }
    public function buildMySiteOutbound(string $siteName, Request $request)
    {
        try {
            if (!$siteName) {
                throw new \Exception('Site name is required.');
            }
            $siteObject = DB::table('my_site')->where('site_name', $siteName)->first();
            if (!$siteObject) {
                throw new \Exception('Site not found.');
            }

            $diskStorage = Storage::disk('my_site_storage');
            $pathSH = $diskStorage->get($siteObject->sh_content_dir);

            $output = null;
            $return_var = null;
            exec("sudo /bin/bash $pathSH  2>&1", $output, $return_var);

            $mailDev = DB::table('users')->where('name',$request->user)->first();
            if (!$mailDev) {
                throw new \Exception("Developer name not found with $request->user.");
            }
            $update = [
                'last_build' => now(),
                'last_user_build'=> $mailDev->id
            ];

            if ($return_var === 1) {
                $update['last_build_fail'] = now();
            } else {
                $update['last_build_success'] = now();
            }

            $update['path_log'] = $siteObject->site_name .'/log/execution_' .  $output[0] .'.log';
            // Generate env file
            if ($siteObject->is_generate_env){
                $custom = [
                    'PORT_PM2'      => $siteObject -> port_pm2,
                    'VITE_API_URL'  => $siteObject -> api_endpoint_url,
                    'VITE_WEB_NAME' => $siteObject -> site_name,
                ];
                generateEnvFile($siteObject->path_source_code, $custom);
            }
            $status = DB::table('my_site')->where('site_name', $siteName)->update($update);

            // Send email
            if ($status && !empty($logPath)) {
                $mailContent = Storage::disk('my_site_storage')->get($logPath);
                Mail::to(env('DEV_EMAIL'))->send(new BuildNotification(
                    $siteObject->site_name,
                    'success',
                    now()->format('Y-m-d H:i:s'),
                    $mailContent,
                    $mailDev->email
                ));
            }

            return response()->json([
                'status' => 200,
                'message' => 'Build completed successfully'
            ]);
        } catch (\Exception $exp) {
            return response()->json(['status' => $exp->getCode(), 'message' => $exp->getMessage()]);
        }





        return response()->json(['error' => 'Site not found'], 404);
    }
    public function getAllDetailSiteByID(Request $request): false|JsonResponse
    {
        if (!$request->has('site_id')) {
            return false;
        }
        $siteObject = DB::table('my_site')
            ->selectRaw('my_site.id as id,port_pm2,path_source_code, site_name, api_endpoint_url,is_generate_env,path_log, sh_content_dir, last_build, my_site.created_at as created_at, my_site.updated_at as updated_at, users.name as name, last_build_success, last_build_fail')
            ->join('users', 'my_site.last_user_build', '=', 'users.id')
            ->where('my_site.id', $request->site_id)
            ->first();
        $shContent = '';
        if (!empty($siteObject)) {
            $shContent = Storage::disk('my_site_storage')->get($siteObject->sh_content_dir);//configured in the file filesystems.php
            $allNodeEnvVariables = readNodeEnv($siteObject->path_source_code);

        }
        $data = [
            'sh_content'         => $shContent . "\n\n\n\n ======================ENV File =================== \n" . $allNodeEnvVariables,
            'site_name'          => $siteObject -> site_name,
            'last_path_log'      => $siteObject -> path_log,
            'sh_content_dir'     => $siteObject -> sh_content_dir,
            'created_at'         => $siteObject -> created_at,
            'last_user_build'    => $siteObject -> name,
            'last_build_success' => $siteObject -> last_build_success,
            'last_build_fail'    => $siteObject -> last_build_fail,
            'last_build'         => $siteObject      -> last_build ,
            'port_pm2'         => $siteObject      -> port_pm2 ,
            'path_source_code'         => $siteObject      -> path_source_code ,
            'api_endpoint_url'         => $siteObject      -> api_endpoint_url ,
            'is_generate_env'         => $siteObject      -> is_generate_env ,
            'id'         => $siteObject      -> id ,
        ];
        return response()->json($data);
    }


    private function generateShellScript($siteName, $folderSourcePath,$includePM2)
    : string {
        $pm2Process = "";
        $pathProject = env('PATH_PROJECT', '/var/www/html');
        if ($includePM2) {
            $pm2Process = <<<EOT
# Run pm2 script
sudo sh pm2_dev.sh >> \$LOG_FILE 2>&1
if [ $? -ne 0 ]; then
    echo "Error: pm2_dev.sh script failed" >> \$LOG_FILE
    echo \$time
    exit 1
fi
EOT;
        }
        return <<<EOT
#!/bin/bash
time="$(date +%s)"
LOG_FILE="$pathProject/build_source_manage/storage/my_site_storage/$siteName/log/execution_\$time.log"
touch \$LOG_FILE

echo "-----\$(date): Start script-----" >> \$LOG_FILE

echo "-----\$(date): cd $folderSourcePath -----" >> \$LOG_FILE
cd $folderSourcePath >> \$LOG_FILE  2>&1
if [ $? -ne 0 ]; then
    echo "Error: Failed to change directory" >> \$LOG_FILE
    echo \$time
    exit 1
fi

echo "-----\$(date): Git Status Source -----" >> \$LOG_FILE
sudo git status >> \$LOG_FILE 2>&1
if [ $? -ne 0 ]; then
    echo "Error: git status failed" >> \$LOG_FILE
    echo \$time
    exit 1
fi
echo "-----\$(date): Start Pull Source-----" >> \$LOG_FILE
sudo git pull >> \$LOG_FILE 2>&1
if [ $? -ne 0 ]; then
    echo "Error: git pull failed" >> \$LOG_FILE
    echo \$time
    exit 1
fi

echo "-----\$(date): NPM Install-----" >> \$LOG_FILE
sudo npm install >> \$LOG_FILE 2>&1

echo "-----\$(date): NPM BUILD-----" >> \$LOG_FILE
sudo npm run build >> \$LOG_FILE 2>&1
if [ $? -ne 0 ]; then
    echo "Error: npm run build fail" >> \$LOG_FILE
    echo \$time
    exit 1
fi

$pm2Process

echo "-----\$(date): Finish-----" >> \$LOG_FILE
echo \$time
exit 0
EOT;
    }
}
