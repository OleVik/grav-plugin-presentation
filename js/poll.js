// https://github.com/axios/axios/issues/164#issuecomment-327837467
axios.interceptors.response.use(undefined, function axiosRetryInterceptor(err) {
  var config = err.config;
  if(!config || !config.retry) return Promise.reject(err);
  config.__retryCount = config.__retryCount || 0;
  if(config.__retryCount >= config.retry) {
      return Promise.reject(err);
  }
  config.__retryCount += 1;
  var backoff = new Promise(function(resolve) {
      setTimeout(function() {
          resolve();
      }, config.retryDelay || 1);
  });
  return backoff.then(function() {
      return axios(config);
  });
});

function refresh() {
  axios.get(location.origin + '/presentationapi', {
    params: {
      mode: 'get'
    },
    retry: 5,
    retryDelay: 5000
  })
  .then(function (response) {
    var now = new Date();
    console.log(now.getHours() + ":" + now.getMinutes() + ":" + now.getSeconds());
    if (response.data != '') {
      console.log(response.status, response.data);
      var command = response.data
      axios.get(location.origin + '/presentationapi', {
        params: {
          mode: 'remove'
        }
      }).then(function (response) {
        console.log(response.status, response.data);
        if (command == 'next') {
          Reveal.next();
        } else if (command == 'previous') {
          Reveal.prev();
        }
        switch(command) {
          case 'next':
            Reveal.next();
            break;
          case 'previous':
            Reveal.prev();
            break;
          case 'left':
            Reveal.left();
            break;
          case 'right':
            Reveal.right();
            break;
          case 'up':
            Reveal.up();
            break;
          case 'down':
            Reveal.down();
            break;
          case 'prevFragment':
            Reveal.prevFragment();
            break;
          case 'nextFragment':
            Reveal.nextFragment();
            break;
          case 'toggleOverview':
            Reveal.toggleOverview();
            break;
        }
      })
      .catch(function (error) {
        console.log(error);
      });;
    }
  })
  .catch(function (error) {
    console.log(error);
  });
  setTimeout(refresh, 2000);
}

refresh();