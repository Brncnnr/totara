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
:: Install the Totara Machine Learning service on Windows
setlocal EnableExtensions DisableDelayedExpansion
set parent=%~dsp0
set me=%~nx0

set /A ml_python_min_ver=37000
set /A ml_python_max_ver=39000

:: Check that the environmental variables have been called
set halt=0
call scripts\functions.cmd check_env_vars
if 1 == %halt% (
    goto:exit
)

:: Check that our python version is correct
set halt=0
call scripts\functions.cmd check_python_version
if 1 == %halt% (
    goto:exit_error
)

:: Install the required packages
echo.
echo Installing Python packages, this may take some time...
call pip install --no-cache-dir -r requirements.txt

if 1 == %ERRORLEVEL% (
    color
    echo.
    echo There was a problem installing the required packages.
    goto:exit_error
)
color

echo Package installation complete.

echo.
echo Installation complete. Please start the service with cmd /c "%parent%start.cmd"
goto:exit

:exit_error
echo Installation finished with errors.
exit /B 1

:exit
exit /B 0