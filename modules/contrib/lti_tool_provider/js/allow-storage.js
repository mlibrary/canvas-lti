document.addEventListener('DOMContentLoaded', function () {
  // Note: Only Firefox and Safari support hasStorageAccess currently.
  // TODO: Figure out why Firefox says hasStorageAccess is false but still allows LTI tool to load. This covers Safari for now.
  if (typeof document.hasStorageAccess === 'function' && typeof document.requestStorageAccess === 'function' && navigator.userAgent.indexOf("Safari") > -1) {
    document.hasStorageAccess().then((hasAccess) => {
      if (hasAccess) {
        console.log('access allowed');
      }
      else {
        console.log('access denied');
        site = window.drupalSettings.ltiToolProvider.allowStorage.site;
        btn = document.createElement('button');
        btn.innerHTML = '<strong>Please accept to continue</strong>';
        btn.classList.add('button');
        text = document.createElement('div');
        text.classList.add('messages');
        text.classList.add('messages--error');
        //Put it in the center of the page to make sure its noticed.
        text.style.position = 'absolute';
        text.style.top = '50%';
        text.style.width = '100%';
        text.style.textAlign = 'center';
        text.style.zIndex = '1000';
        text.innerHTML = '<p style="margin:0; max-width:100%;"><strong>This tool requires session cookies.</strong><br/>You will be asked to redirect to the tool at ' + site + '<br/><em>and then asked again to to allow them on this site</em>.</p>';
        btn.addEventListener('click', function() {
          document.requestStorageAccess().then((e) => {
            if (document.cookie && document.cookie.split(';').some((item) => item.trim().startsWith('allowstorage='))) {
              alert('This tool will now reload')
              document.body.innerHTML = '<h1>Reloading..</h1>';
              document.body.style.backgroundColor = '#fff';
              window.location.reload()
            } else {
              if (window.confirm('You will now be redirected to ' + site + ' to allow cookies')) {
                requestwindow()
              }
              else {
                alert('You must allow a session cookie to log in to this tool')
              }
            }
          }).catch(() => {
            //we need to check if this is from a user disallow or not.
            if (document.cookie) {
              alert('You already allowed a session cookie to log in to this tool. This tool will now reload')
              window.location.reload()
            } else {
              //This may happen with Safari after the button click, or may happen after user hits button 'Don't allow'. Be sure they want to be redirected.
              if (window.confirm('You must allow a session cookie to log in to this tool.\nWould you like redirect to ' + site + ' to allow cookies?')) {
                requestwindow()
              }
              else {
                alert('You must allow a session cookie to log in to this tool')
                window.location.reload()
              }
            }
          });
        });
        document.body.insertBefore(text, document.body.firstChild);
        text.appendChild(btn);
      }
    });
    function requestwindow(){
      window.parent.postMessage(
        {
          messageType: 'requestFullWindowLaunch',
          data: site + '/lti/v1p3/launch',
        },
        '*'
      );
    }
  }
}, false);
