redirect = window.drupalSettings.ltiToolProvider.allowStorageFull.redirect;
btn = document.createElement('button');
btn.classList.add('button');
btn.innerHTML = '<strong>Please accept cookies to redirect to '+redirect+'</strong>';
btn.addEventListener('click', function() {
  document.cookie = "allowstorage=true; SameSite=None; Secure";
  window.location.replace(redirect);
});
document.getElementById('allow-storage-js-button').appendChild(btn);
