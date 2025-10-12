<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reset Password</title>
    <style type="text/css">
      @import url('https://fonts.googleapis.com/css2?family=Varela+Round&display=swap');
      @media screen and (max-width: 580px) {
        body {
          max-width: 450px;
        }
      }
      @media screen and (max-width: 450px) {
        body {
          max-width: 380px;
        }
        h1 {
          font-size: 20px;
        }
        p {
          font-size: 15px;
        }
      }
      @media screen and (max-width: 380px) {
        body {
          max-width: 300px;
        }
      }
    </style>
  </head>
  <body
    style="
      font-family: 'Varela Round', sans-serif;
      margin: 0;
      padding: 10px 0;
      background-color: #f6f6f8;
      max-width: 550px;
      margin: 0 auto;
      height: 100%;
    "
  >
    <section>
      <header style="margin: 20px 0">
        <div style="float: left; margin: 3% 0 3% 3%">
          <img src="https://i.postimg.cc/k5HWdGJn/logo.png" alt="1st Mandate" />
        </div>
        <h4
          style="
            font-size: 12px;
            line-height: 19px;
            margin: 3% 2% 4% 3%;
            letter-spacing: 0.5px;
            flex-shrink: 0;
            float: right;
          "
        >
          YOUR ACCOUNT
        </h4>
      </header>
      <div style="clear: both"></div>
      <main style="background: #fff; margin: 0 auto; width: 95%">
        @yield('content')
      </main>
      <footer style="margin: 10px 0 0 0; text-align: center">
        <img
          src="https://i.postimg.cc/xd8L7MSx/footer-Logo.png"
          alt="1st Mandate"
          style="margin: 5px 0"
        />
        <h5
          style="
            text-align: center;
            color: #00000080;
            line-height: 22px;
            font-weight: 100;
            font-size: 14px;
            margin: 3px 0;
          "
        >
          <a
            href="hello@1stmandate.com"
            style="text-decoration: none; color: #00000080; font-size: 14px"
          >
            hello@1stmandate.com
          </a>
        </h5>
        <h5
          style="
            text-align: center;
            color: #00000080;
            line-height: 22px;
            font-weight: 100;
            font-size: 14px;
            margin: 3px 0;
          "
        >
          copyright © 2024 1st Mandate. All Rights Reserved.
        </h5>
      </footer>
    </section>
  </body>
</html>
