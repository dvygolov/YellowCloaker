(function () {
  const endpoint = {CONVERSION_API_URL_JSON};
  const clickId = {CLICK_ID_JSON};

  window.ytdsConversion = function (status) {
    const normalizedStatus = String(status || '').trim();
    if (!endpoint || !clickId || !normalizedStatus) {
      return Promise.reject(new Error('Status and clickid are required.'));
    }
    const body = new URLSearchParams();
    body.set('clickid', clickId);
    body.set('status', normalizedStatus);
    return fetch(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
      body: body.toString(),
      keepalive: true,
      credentials: 'same-origin'
    }).then(function (response) {
      return response.json().then(function (payload) {
        if (!response.ok || !payload.ok) {
          throw new Error(payload.message || payload.code || 'Conversion rejected.');
        }
        return payload;
      });
    });
  };
})();
