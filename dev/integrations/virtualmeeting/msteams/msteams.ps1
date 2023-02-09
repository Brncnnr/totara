<#
.SYNOPSIS
  Create a new Azure app for the Microsoft Teams virtual meeting plugin for a development website.

.DESCRIPTION
  Register a new Azure app with required configuration for the Microsoft Teams virtual meeting plugin.

  ** THIS SCRIPT IS INTENDED FOR DEVELOPMENT PURPOSES ONLY **

  Do not use in production.

  The script must be run as administrator for the first time.

.EXAMPLE
  PS C:\> .\msteams.ps1 -Name 'Totara Virtual Meeting' -Uri 'https://totara.example.com/'
  Register an Azure app for the specified website with the specified name.

.EXAMPLE
  PS C:\> .\msteams.ps1 -TenantId '31415926-5358-9793-2384-626433832795' -Name 'Totara Virtual Meeting' -Uri 'https://totara.example.com/'
  A tenant ID might be required if login fails.

.LINK
  For production use, please refer to the following documentation:
  https://help.totaralearning.com/display/TH13/Working+with+virtual+rooms
#>
Param(
  [string] $TenantId,

  [Parameter(Mandatory=$true)]
  [string] $Name,

  [Parameter(Mandatory=$true)]
  [ValidatePattern('^https://[^/]+')]
  [string] $Uri
)

if (!(Get-Package AzureAD -ErrorAction Ignore)) {
  Write-Host 'Installing AzureAD package'
  Install-Module AzureAD -AllowClobber -Scope CurrentUser
}

if ($Uri -and $Uri.substring($Uri.length - 1) -ne '/') {
  $Uri += '/'
}

if ($TenantId) {
  $azCtx = Connect-AzureAD -TenantId $TenantId
} else {
  $azCtx = Connect-AzureAD
}
if (!$azCtx) {
  throw 'Cannot connect to Azure AD'
}

$oauth2CallbackUri = $Uri + 'integrations/virtualmeeting/auth_callback.php/msteams'
$audience = 'AzureADMultipleOrgs'

Write-Host 'Register a new app'
$app = New-AzureADMSApplication -DisplayName $Name -Web @{RedirectUris = @($oauth2CallbackUri)} -SignInAudience $audience
$objId = $app.Id

Write-Host 'Add API permissions'
$reqaccs = New-Object System.Collections.Generic.List[Microsoft.Open.MsGraph.Model.RequiredResourceAccess]
$graph = Get-AzureADServicePrincipal -Filter "DisplayName eq 'Microsoft Graph'"
$reqacc = New-Object Microsoft.Open.MsGraph.Model.RequiredResourceAccess
$reqacc.ResourceAppId = $graph.AppId
$reqacc.ResourceAccess = New-Object System.Collections.Generic.List[Microsoft.Open.MsGraph.Model.ResourceAccess]
$exposedPermissions = $graph.Oauth2Permissions
$permissions = @('User.Read', 'OnlineMeetings.ReadWrite', 'email', 'offline_access', 'openid', 'profile')
foreach ($permission in $permissions) {
  foreach($exposedPermission in $exposedPermissions) {
    if ($exposedPermission.Value -eq $permission) {
      $resacc = New-Object Microsoft.Open.MsGraph.Model.ResourceAccess
      $resacc.Type = 'Scope'
      $resacc.Id = $exposedPermission.Id
      $reqacc.ResourceAccess.Add($resacc)
    }
  }
}
$reqaccs.Add($reqacc)
Set-AzureADMSApplication -ObjectId $app.Id -RequiredResourceAccess $reqaccs

Write-Host 'Add client secret'
$pass = New-AzureADMSApplicationPassword -ObjectId $objId -PasswordCredential @{DisplayName = 'Totara virtual meeting plugin'}

Write-Host
Write-Host -ForegroundColor Cyan 'Please fill in the plugin settings with the following:'
Write-Host 'App ID: ' -NoNewline
Write-Host $app.AppId -ForegroundColor Black -BackgroundColor White
Write-Host 'Client secret: ' -NoNewline
Write-Host $pass.SecretText -ForegroundColor Black -BackgroundColor White
Write-Host
Write-Host -ForegroundColor Yellow 'The client secret will expire: ' -NoNewline
Write-Host -ForegroundColor Yellow $pass.EndDateTime.ToLocalTime()
