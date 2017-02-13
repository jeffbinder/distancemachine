<!DOCTYPE html>
<html>
 <head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <link rel="apple-touch-icon" sizes="57x57" href="/apple-touch-icon-57x57.png">
  <link rel="apple-touch-icon" sizes="72x72" href="/apple-touch-icon-72x72.png">
  <link rel="apple-touch-icon" sizes="60x60" href="/apple-touch-icon-60x60.png">
  <link rel="apple-touch-icon" sizes="76x76" href="/apple-touch-icon-76x76.png">
  <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96">
  <link rel="icon" type="image/png" href="/favicon-16x16.png" sizes="16x16">
  <meta name="msapplication-TileColor" content="#da532c">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <script src="http://d3js.org/d3.v3.min.js" charset="utf-8"></script>
  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
  <script src="jquery.watermark.min.js"></script>
  <script src="config.js"></script>
  <script src="wordinfo.js"></script>
  <script src="entry.js"></script>
  <link rel="stylesheet" type="text/css" href="http://fonts.googleapis.com/css?family=Inika:700">
  <link rel="stylesheet" type="text/css" href="http://fonts.googleapis.com/css?family=Ubuntu:400">
  <link rel="stylesheet" type="text/css" href="http://fonts.googleapis.com/css?family=Ubuntu:700">
  <link rel="stylesheet" type="text/css" href="site.css">
  <link rel="stylesheet" type="text/css" href="wordinfo.css">
  <link rel="stylesheet" type="text/css" href="entry.css">
  <title>The Distance Machine</title>
  <script>
archive = false;
  </script>
 </head>
 <body>
 <div id="main-area">
  <div class="title-area">
   <div class="title">
    The Distance Machine
   </div>
  </div>
  <div class="boxholder" id="text-area">
   <div class="leftbox">
    <div class="text" style="margin-bottom:10px">
     The Distance Machine uses historical word usage data from Google Books to highlight words in a text that were unusual at a selected point in time.  Want to learn more?  <a href="about">Click here</a>.
    </div>
    <hr/>
    <div>
     <a href="howitworks">How it works</a>
     | <a href="examples">Examples</a>
     | <a href="activity">Recent searches</a>
    </div>
   </div>
   <div class="rightbox">
    <div style="font-size:16pt">
     Try it now!
    </div>
    <hr/>
    <div class="text" style="margin-bottom:10px">
     Experiment with our edition of Walt Whitman's <a href="text/pRbZXEmi"><i>Leaves of Grass</i></a>.
    </div>
   </div>
  </div>
  <div class="box" id="input-area" style="clear:both">
   <div style="font-size:16pt;margin-bottom:10px">
    Enter your own text:
   </div>
   <div>
    <input id="title-input" type="text" name="title" style="width:600px">
   </div>
   <div style="margin-top:5px">
    <textarea id="text-input" rows="10" cols="80" name="text" style="width:600px"></textarea>
   </div>
   <div style="margin-top:5px;width:600px">
    <div style="float:left">
     Choose a corpus:
     <select id="corpus-input" name="corpus">
      <option value="us">Google Books US English, 1750-2009</option>
      <option value="gb">Google Books UK English, 1750-2009</option>
      <option value="eebotcp1">EEBO-TCP Phase I: 25,000 English books, 1500-1700</option>
     </select>
    </div>
    <div style="float:right">
     <input type="button" onclick="generate()" value="Go!">
    </div>
    <div style="clear:both"></div>
   </div>
  </div>
  <div class="box" id="word-lookup-area">
   Or look up a word: <input type="text" id="word-lookup">
    <div style="float:right">
     <select id="word-lookup-corpus-input" name="corpus">
      <option value="us">US English</option>
      <option value="gb">UK English</option>
      <option value="eebotcp1">EEBO-TCP Phase I</option>
     </select>
    </div>
  </div>
  <div class="box" id="footer">
   <div style="float:right"><a href="http://newmedialab.cuny.edu/people/jeffrey-binder/">About me</a> | <a href="http://github.com/jeffbinder/distancemachine">Source code</a> | <a href="tos">Legal</a></div>
   <div>This site copyright © 2014-2015 Jeffrey Binder.</div>
  </div>
  </div>
  <div id="status-box">
    <div>Generating annotations: <span id="percentage">0%</span></div>
    <div><progress id="progress-bar" max="100" value="0"></progress></div>
    <div style="float:right;margin-top:10px">
     <button onclick="cancel_generation()">Cancel</button>
    </div>
  </div>
  <div id="error-box">
    <div>Error generating annotations!</div>
  </div>
  <div>
   <form id="hidden-form" action="unsaved" method="POST">
   </form>
  </div>
  <div id="word-info">
    <div class="back-button"><a href="javascript:pop_history()">&lt;back</a></div>
    <div>Selected word: <span id="selected-word"></span></div>
    <hr />
    Frequency in the <span id="corpus-name"></span> corpus (click to view):
    <div id="word-usage-chart"></div>
    <div><span id="usage-periods-text"></span></div>
    <hr />
    <div id="definition-area"><span id="definitions"></span></div>
  </div>
  <div id="reverse-lookup-box">
    <div class="back-button"><a href="javascript:pop_history()">&lt;back</a></div>
    <div id="reverse-lookup-text"></div>
    <hr />
    <div id="reverse-lookup-word-area"><span id="reverse-lookup-words"></span></div>
  </div>
 </body>
</html>
