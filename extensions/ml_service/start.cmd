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


@echo off
:: Start the Totara Machine Learning service on Windows
setlocal EnableExtensions DisableDelayedExpansion
set parent=%~dsp0
set me=%~nx0

set /A ml_python_min_ver=37000
set /A ml_python_max_ver=39000

set command=%1

set halt=0
call scripts\functions.cmd check_env_vars
if 1 == %halt% (
    goto:exit
)
set halt=0
call scripts\functions.cmd check_start_env_vars
if 1 == %halt% (
    goto:exit
)

set halt=0
call scripts\functions.cmd check_python_version
if 1 == %halt% (
    goto:exit
)

:: Lastly, python doesn't want the quotes
set ML_MODELS_DIR=%dir_models:"=%
set ML_LOGS_DIR=%dir_logs:"=%

call %python_path% -m nltk.downloader stopwords -d %ML_MODELS_DIR%

:: Dev mode
if defined ML_DEV (
    cd service
    set FLASK_ENV=Development
    call %python_path% -m flask run --host=0.0.0.0
    goto:exit
)

:: Production mode as default
call %python_path% -m waitress --listen=%bind_addr% --call "service.app:create_app"

:exit
exit /B