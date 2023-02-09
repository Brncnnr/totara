:: This file is part of Totara Enterprise Extensions.
::
:: Copyright (C) 2021 onward Totara Learning Solutions LTD
::
:: Totara Enterprise Extensions is provided only to Totara
:: Learning Solutions LTD's customers and partners, pursuant to
:: the terms and conditions of a separate agreement with Totara
:: Learning Solutions LTD or its affiliate.
::
:: If you do not have an agreement with Totara Learning Solutions
:: LTD, you may not access, use, modify, or distribute this software.
:: Please contact [licensing@totaralearning.com] for more information.
::
:: @author Cody Finegan <cody.finegan@totaralearning.com>
:: @package ml_service


:: Collection of functions used by the Windows installation & execution scripts
@echo off
call:%~1
goto:eof


:: Check that environmental variables have been provided
:check_env_vars
    set /A param_count=0

    set dir_models=%parent%data\models
    set dir_logs=%parent%data\logs
    set bind_addr=127.0.0.1:5000

    if defined ML_BIND (
        set bind_addr=%ML_BIND%
    )

    if defined ML_MODELS_DIR (
        set dir_models=%ML_MODELS_DIR%
    ) else (
        echo Using default path for models: %dir_models%
        echo If you want to use a different path, please define ML_MODELS_DIR environment variable:
        echo For example: set ML_MODELS_DIR=c:\path\to\models ^&^& %me%
        echo.
    )

    if defined ML_LOGS_DIR (
        set dir_logs=%ML_LOGS_DIR%
    ) else (
        echo Using default path for logs: %dir_logs%
        echo If you want to use a different path, please define ML_LOGS_DIR environment variable:
        echo For example: set ML_LOGS_DIR=c:\path\to\logs ^&^& %me%
        echo.
    )

    :: We fix quotes on these paths. Delete any quotes provided & then add our own
    set dir_models=%dir_models:"=%
    set dir_models="%dir_models%"
    set dir_logs=%dir_logs:"=%
    set dir_logs="%dir_logs%"

    if %dir_models% == "" (
        echo ML_MODELS_DIR was not provided. Please define the ML_MODELS_DIR environment variable.
        set halt=1
        goto:eof
    )

    if %dir_logs% == "" (
        echo ML_LOGS_DIR was not provided. Please define the ML_LOGS_DIR environment variable.
        set halt=1
        goto:eof
    )

    if %dir_logs% == %dir_models% (
        echo The logs & models directories must not be the same.
        echo Got: '%dir_models%' and '%dir_logs%'
        set halt=1
        goto:eof
    )

    :: Check that the directories actually exist
    if not exist %dir_models% (
        echo The models directory %dir_models% does not exist. Creating...
        mkdir %dir_models%
        if 0 == %ERRORLEVEL% (
            echo Created.
        ) else (
            echo Could not create '%dir_models%'. Exiting.
            set halt=1
            goto:eof
        )
    )

    if not exist %dir_logs% (
        echo The logs directory %dir_logs% does not exist. Creating...
        mkdir %dir_logs%
        if 0 == %ERRORLEVEL% (
            echo Created.
        ) else (
            echo Could not create '%dir_logs%'. Exiting.
            set halt=1
            goto:eof
        )
    )

    echo.
    echo Models directory is %dir_models%
    echo Logs directory is %dir_logs%

    goto:eof

:check_start_env_vars
    if not defined ML_TOTARA_URL (
        echo ML_TOTARA_URL was not defined. Please set it in your environmental variables, or directly.
        echo For example: set ML_TOTARA_URL=http://totaraurl ^&^& %me%
        echo.
        set halt=1
    )

    if not defined ML_TOTARA_KEY (
        echo ML_TOTARA_KEY was not defined. Please set it in your environmental variables, or directly.
        echo For example: set ML_TOTARA_KEY=auniquetoken ^&^& %me%
        echo.
        set halt=1
    )

    goto:eof

:: This will print an error if no python is found
:check_python_version
    echo.
    echo Checking python version...

    if exist pyver.txt del pyver.txt

    :: Check the generic first
    set python_path=python3
    2>NUL call %python_path% -c "import sys; print(int(float(f\"{sys.version_info.major}.{sys.version_info.minor}\") * 10000))" > pyver.txt

    :: If that failed, try the more specific python3
    if not 0 == %ERRORLEVEL% (
        set python_path=python
        2>NUL call python -c "import sys; print(int(float(f\"{sys.version_info.major}.{sys.version_info.minor}\") * 10000))" > pyver.txt
    )
    :: If that failed we cannot find python
    if not 0 == %ERRORLEVEL% (
        echo.
        echo Could not find a version of python installed. Is python installed and available on the path?
        echo Try executing: python --version
        echo And see if it prints a version, or if you get any other message.
        set halt=1
        goto:eof
    )

    set /p pyver=<pyver.txt
    del pyver.txt 2>NUL   

    if "" == "%pyver%" (
        echo.
        echo Could not find a version of python installed. Is python installed and available on the path?
        echo Try executing: python --version
        echo And see if it prints a version, or if you get any other message.
        set halt=1
        goto:eof
    )

    set invalid_python=0
    if "%pyver%" lss "%ml_python_min_ver%" (
        set invalid_python=1
        echo Installed python was less than expected version
    )
    if "%pyver%" gtr "%ml_python_max_ver%" (
        set invalid_python=1
        echo Installed python was greater than expected version
    )
    if 1 == %invalid_python% (
        echo.
        echo Could not find a valid version of python installed. Please check the readme for installation instructions & required versions.
        set halt=1
    )

    echo Python found.

    goto:eof


:check_pip
    echo "Checking for pip."
    set pip_command=pip
    call %pip_command% -V 2>&1


:download_models
    :: Check that git exists
    call git --version 1>NUL 2>&1
    
    if not 0 == %ERRORLEVEL% (
        :: We couldn't find git, so print a error about that
        echo.
        echo To download the models you need to have git installed. Otherwise check the documentation about manually providing the models to the service.
        goto:eof
    )

    set remote_repo=ssh://git@git.totaralearning.com:7999/general/ml-models.git
    set remote_repo_https=https://git.totaralearning.com/scm/general/ml-models.git

    set existing_model=%dir_models:"=%\offensive-language-classifier
    if not exist "%existing_model%" (
        goto:do_download
    )
    echo There are already models in your models directory %dir_models%
    echo Please clean-up models directory if you want reinstall them.
    goto:eof


:do_download
    cd %dir_models%
    set mins= (this may take a few minutes^^^)
    
    if exist .git (
        :: Do a git pull
        echo.
        echo Updating models...%mins%
        echo.
        call git pull --depth=1 origin master
        set download_result=%ERRORLEVEL%
    ) else (
        :: Do a git clone
        echo.
        echo Downloading the models...%mins%
        echo.

        if defined ML_MODELS_GIT_HTTPS (
            echo Using HTTPS repo due to configuration option
            set remote_repo=%remote_repo_https%
        )

        call git clone --depth=1 "%remote_repo%" --branch master --single-branch .
        set download_result=%ERRORLEVEL%
    )

    echo.
    cd %parent%

    if 0 == %download_result% (
        echo Model downloading is complete.
        goto:eof
    )

    set halt=1
    echo There was a problem trying to download the models.
    goto:eof
