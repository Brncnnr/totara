# Machine Learning Service

The ML Service currently includes the Recommendation Engine feature. Access to this 
feature is provided via an internal API, with the following endpoints:

+ `/health-check`
+ `/similar-items`
+ `/user-items`

At all of these endpoints, the server expects query string parameters delivered in
GET methods.

## Installation

### Supported python versions

The service is tested around the following python versions:

1. Python 3.7
2. Python 3.8
3. Python 3.9

### Configuration

Behaviours by ML Service are controlled via Environmental Variables. These can be defined globally, or in some instances
can be defined at run time. Each method of running the ML Service may have a different way to provide environmental
variables.

| Variable | Used on Install | Used on Start | Default Value | Valid Options | Description |
| -------- | --------------- | ------------- | ------------- | ------------- | ----------- |
| `ML_LOGS_DIR` | ✔️ | ✔️ | data/logs | Path | Path to the folder where logs will be written. If not provided, it will default to data/logs and be auto-created (if possible). |
| `ML_MODELS_DIR` | ✔️ | ✔️ | data/models | Path | Path to the folder where modles will be written. If not provided, it will default to data/models and be auto-created (if possible). |
| `ML_TOTARA_KEY` | ❌ | ✔️ (required) |  | Random string | A random & secret key also known by Totara. This same value must be set in the Totara ml_service_key setting. Service will not start without a value set. |
| `ML_TOTARA_URL` | ❌ | ✔️ (required) |  | Totara URL | The URL to the Totara instance. Such as https://my-totara-instance.com/ |
| `ML_RECOMMENDATION_RETRAIN_FREQ` | ❌ | ✔️ | 1440 | Number of minutes | Number of minutes that the models will be retrained in. Defaults to once every 24 hours. |
| `ML_NUM_THREADS` | ❌ | ✔️ | 4 | Number of processors | Number of processors for model training. Defaults to 4. Should be less than the total processors on your machine. |
| `ML_RECOMMENDATION_ALGORITHM` | ❌ | ✔️ | hybrid | `hybrid`, `partial` or `mf` | The default modelling strategy. Defaults to hybrid, but can also be set to partial and matrix factorization. |
| `ML_DEV` | ❌ | ✔️ (development only) |  | 1 | If set to 1, the service still be started in development mode which provides more information for developers. Never set this in a production system. |
| `ML_BIND` | ❌ | ✔️ |  `*:5000` | IP/Port combination | The IP & port that the waitress service will listen for connections on. Defaults to wildcard port 5000. This is fed straight into the [waitress](https://docs.pylonsproject.org/projects/waitress/en/stable/arguments.html) `--listen` argument. |

When starting the service, the variables marked as required must be specified, otherwise the service will not start.

### Docker

1. Change directory into the `extensions/ml_service` folder.
2. Build the service with Docker

   ```shell
     docker build -t ml_service .
   ```

3. Create a folder to store the data in (for persistence)

   ```shell
      mkdir /path/to/data
   ```

4. Finally, start the service with Docker

   ```shell
      docker run -it -d --name ml_service -p 5000:5000 --env ML_TOTARA_URL=https://totara-instance.com --env ML_TOTARA_KEY=**** -v /path/to/data:/etc/ml/data ml_service
   ```

5. The ML service should now be available on port 5000.

### <a name="linux"></a>Linux

This service has been designed and tested on the following freshly installed OS:

+ Ubuntu 20.04.
+ Ubuntu 18.04
+ Centos 7
+ Centos 8

1. Make sure you have one of the supported versions of python installed
2. Install python's virtual environment if this machine is used by other projects, and activate a virtual environment
3. Change directory into the `extensions/ml_service` folder.
4. Optionally, set the two paths in the environment variables `ML_MODELS_DIR` and `ML_LOGS_DIR`.
5. You will need `gcc` and  `pip` installed

   ```shell
   # Ubuntu:
   sudo apt install -y gcc python3-pip python3-dev git

   # CentOS:
   sudo yum install -y python3 gcc python3-devel python3-pip git
   sudo pip3 install --upgrade pip
   ```

6. If you wish to use non-default folders for logs and models, create a `logs` and `models` directory, which will be used as storage by the service.

    ```shell
      mkdir -p /path/to/storage/logs
      mkdir -p /path/to/storage/models
    ```

7. Install the service using one of the following commands:

   ```shell

      ./install.sh

      # If you have not set the environment variables
      ML_MODELS_DIR=/path/to/storage/models ML_LOGS_DIR=/path/to/storage/logs ./install.sh
   ```

8. Follow the prompt as the service is installed.
9. Next start the service using one of the following commands:

   ```shell
      # If you have the environment variables set
      ./start.sh

      # If you have not set the environment variables
      ML_MODELS_DIR=/path/to/storage/models ML_LOGS_DIR=/path/to/storage/logs ML_TOTARA_URL=https://totara-instance.com ML_TOTARA_KEY=**** ./start.sh
   ```

   You can also start the service with supervisor by following the instructions as suggested in the section [Starting the Service with Supervisor on Linux](#supervisor).
10. Check that you can access [http://localhost:5000/]

### Windows

1. Make sure you have the Microsoft Visual C++ 14.2 or higher build tools installed. Instructions can be found here https://wiki.python.org/moin/WindowsCompilers
2. Make sure you have the correct version of python installed with Windows
3. Confirm that both python and pip are available on your path (try running `python --version` and `pip --version` to see if they return correct results).
4. Make sure you've updated pip `pip install --upgrade pip`
5. Install python's virtual environment if this machine is used by other projects, and activate a virtual environment
6. Check that you've disabled both versions of Python in Settings -> App Execution Aliases, as they may interfere with your installation.
7. Change directory into the `extensions/ml_service` folder.
8. Create a `logs` and `models` directory, which will be used as storage by the service.
    ```shell
      mkdir data\models
      mkdir data\logs
    ```
9. Optionally, set the two paths in the environment variables `ML_MODELS_DIR` and `ML_LOGS_DIR`.
10. Set the required environmental variables (`ML_TOTARA_URL` and `ML_TOTARA_KEY`).
11. Install the service using the following command:
   ```commandline 
      cmd /c install.cmd
   ```
12. Follow any prompts as the service is installed.
13. Once installation is complete, start the service using the following command:
   ```commandline 
      cmd /c start.cmd
   ```
14. Check that you can access http://your-ip-address:5000



### <a name="supervisor"></a>Starting the Service with Supervisor on Linux

You can use supervisor to start the service. The supervisor allows its users in controlling processes, monitoring process status and restarting on a crash. The supervisor can be installed by following the instructions at [Supervisor Installing](http://supervisord.org/installing.html) either via PyPi or via Distribution Package. Follow the following steps for starting the service with supervisor:

1. Install supervisor
2. Create a directory for storing the supervisor logs for the service. For example, `/var/log/ml_service`
3. Note the path of python executable that has the required libraries mentioned in the `requirements.txt` file
4. Note the paths of the models and logs directories as have been set in the above section [Linux](#linux)
5. Create a file called `ml_service.conf` and save it in the directory `/etc/supervisor/conf.d`. A template of the file is as below:

   ```ini
   [program:ml_service]
   directory=/path/to/project
   environment=
     ML_MODELS_DIR="/path/to/storage/models",
     ML_LOGS_DIR="/path/to/storage/logs",
     ML_TOTARA_URL="https://totara.com/server",
     ML_TOTARA_KEY="****",
     PYTHON_EXEC="/path/to/python"
   command=/path/to/project/start.sh
   autostart=true
   autorestart=true
   stopsignal=INT
   stopasgroup=true
   killasgroup=true
   stderr_logfile=/var/log/ml_service/ml_service.err.log
   stdout_logfile=/var/log/ml_service/ml_service.out.log
   ```

   Here the `/path/to/project` is the absolute path of the project. The `/path/to/storage/models` is absolute path of the models directory and `/path/to/storage/logs` is that of the logs as mentioned in the section [Linux](#linux) above. 
6. Start the service with supervisor using the following commands:

   ```shell
   sudo supervisorctl reread
   sudo service supervisor restart
   ```

   To check the status of all apps monitored by the supervisor use

   ```shell
   sudo supervisorctl status
   ```

   To stop the service any time use

   ```shell
   sudo supervisorctl stop ml_service
   ```

   and to start it again use

   ```shell
   sudo supervisorctl start ml_service
   ```

## Usage

In your Totara instance open the Machine Learning admin settings screen and configure the service address and key to
point to this instance. Both this service and Totara must share the same key in order to communicate.

Once configured you can check the connection and general status of the service by calling (from Totara) the following
command line script:

```shell
php server/ml/service/cli/healthcheck.php
```

The healthcheck script can be used to diagnose problems and identify next steps to take.
