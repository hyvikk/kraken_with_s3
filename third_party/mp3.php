<?php

class mp3
{
  var $str;
  var $time;
  var $frames;
  var $fp;
  var $filesize;
  
  function __construct($path="")
  {
    if($path!="" && ($this->fp = @fopen($path, 'rb')))
    {
      $this->str = file_get_contents($path);
      @$this->filesize = filesize($path);
    }
  }

  function setStr($str)
  {
    $this->str = $str;
  }

  function getStr()
  {
    return $this->str;
  }

  function getLength()
  {
    return $this->time;
  }

  function getStart()
  {
    $currentStrPos = -1;
    while (true)
    {
      $currentStrPos = strpos($this->str, chr(255), $currentStrPos+1);
      if ($currentStrPos === false)
        return 0;

      $str = substr($this->str,$currentStrPos,4);
      $strlen = strlen($str);
      $parts = array();
      for($i=0;$i < $strlen;$i++)
        $parts[] = $this->decbinFill(ord($str[$i]),8);

      if ($this->doFrameStuff($parts) === false)
        continue;

      return $currentStrPos;
    }
  }

  function setFileInfoExact()
  {
    $maxStrLen = strlen($this->str);
    $currentStrPos = $this->getStart();

    $framesCount=0;
    $time = 0;
    while($currentStrPos < $maxStrLen)
    {
      $str = substr($this->str,$currentStrPos,4);
      $strlen = strlen($str);
      $parts = array();
      for($i=0;$i < $strlen;$i++)
        $parts[] = $this->decbinFill(ord($str[$i]),8);

      if($parts[0] != "11111111")
      {
        if(($maxStrLen-128) > $currentStrPos)
        {
          return false;
        }
        else
        {
          $this->time = $time;
          $this->frames = $framesCount;
          return true;
        }
      }
      $a = $this->doFrameStuff($parts);
      $currentStrPos += $a[0];
      $time += $a[1];
      $framesCount++;
    }
    $this->time = $time;
    $this->frames = $framesCount;
    return true;
  }

  function extract($start,$length)
  {
    $maxStrLen = strlen($this->str);
    $currentStrPos = $this->getStart();
    $framesCount=0;
    $time = 0;
    $startCount = -1;
    $endCount = -1;
    while($currentStrPos < $maxStrLen)
    {
      if($startCount==-1&&$time>=$start)
      {
        $startCount = $currentStrPos;
      }
      if($endCount==-1&&$time>=($start+$length))
      {
        $endCount = $currentStrPos-$startCount;
      }
      $doFrame = true;
      $str = substr($this->str,$currentStrPos,4);
      $strlen = strlen($str);
      $parts = array();
      for($i=0;$i < $strlen;$i++)
      {
        $parts[] = $this->decbinFill(ord($str[$i]),8);
      }
      if($parts[0] != "11111111")
      {
        if(($maxStrLen-128) > $currentStrPos)
        {
          $doFrame = false;
        }
        else
        {
          $doFrame = false;
        }
      }
      if($doFrame)
      {
        $a = $this->doFrameStuff($parts);
        $currentStrPos += $a[0];
        $time += $a[1];
        $framesCount++;
      }
      else
        break;
    }
    $mp3 = new mp3();
    if($endCount == -1)
    {
      $endCount = $maxStrLen-$startCount;
    }
    if($startCount!=-1&&$endCount!=-1)
    {
      $mp3->setStr(substr($this->str,$startCount,$endCount));
    }
    return $mp3;
  }

  function decbinFill($dec,$length=0)
  {
    $str = decbin($dec);
    $nulls = $length-strlen($str);
    if($nulls>0)
    {
      for($i=0;$i<$nulls;$i++)
      {
        $str = '0'.$str;
      }
    }
    return $str;
  }

  function doFrameStuff($parts)
  {
    //Get Audio Version
    $seconds = 0;
    $errors = array();
    switch(substr($parts[1],3,2))
    {
      case '01':
      $errors[]='Reserved audio version';
      break;
      case '00':
      $audio = 2.5;
      break;
      case '10':
      $audio = 2;
      break;
      case '11':
      $audio = 1;
      break;
    }
    //Get Layer
    switch(substr($parts[1],5,2))
    {
      case '01':
      $layer = 3;
      break;
      case '00':
      $errors[]='Reserved layer';
      break;
      case '10':
      $layer = 2;
      break;
      case '11':
      $layer = 1;
      break;
    }
    //Get Bitrate
    $bitFlag = substr($parts[2],0,4);
    $bitArray = array(
      '0000'    => array(0,        0,        0,        0,        0),
      '0001'    => array(32,    32,        32,        32,        8),
      '0010'    => array(64,    48,        40,        48,        16),
      '0011'    => array(96,    56,        48,        56,        24),
      '0100'    => array(128,    64,        56,        64,        32),
      '0101'    => array(160,    80,        64,        80,        40),
      '0110'    => array(192,    96,        80,        96,        48),
      '0111'    => array(224,    112,    96,        112,    56),
      '1000'    => array(256,    128,    112,    128,    64),
      '1001'    => array(288,    160,    128,    144,    80),
      '1010'    => array(320,    192,    160,    160,    96),
      '1011'    => array(352,    224,    192,    176,    112),
      '1100'    => array(384,    256,    224,    192,    128),
      '1101'    => array(416,    320,    256,    224,    144),
      '1110'    => array(448,    384,    320,    256,    160),
      '1111'    => array(-1,    -1,        -1,        -1,        -1)
      );
    $bitPart = $bitArray[$bitFlag];
    $bitArrayNumber;
    if($audio==1)
    {
      switch($layer)
      {
        case 1:
        $bitArrayNumber=0;
        break;
        case 2:
        $bitArrayNumber=1;
        break;
        case 3:
        $bitArrayNumber=2;
        break;
      }
    }
    else
    {
      switch($layer)
      {
        case 1:
        $bitArrayNumber=3;
        break;
        case 2:
        $bitArrayNumber=4;
        break;
        case 3:
        $bitArrayNumber=4;
        break;
      }
    }
    $bitRate = $bitPart[$bitArrayNumber];
    if ($bitRate <= 0)
      return false;
    //Get Frequency
    $frequencies = array(
      1=>array('00'=>44100,
      '01'=>48000,
      '10'=>32000,
      '11'=>'reserved'),
      2=>array('00'=>44100,
      '01'=>48000,
      '10'=>32000,
      '11'=>'reserved'),
      2.5=>array('00'=>44100,
      '01'=>48000,
      '10'=>32000,
      '11'=>'reserved'));
    $freq = $frequencies[$audio][substr($parts[2],4,2)];
    //IsPadded?
    $padding = substr($parts[2],6,1);
    if($layer==3||$layer==2)
    {
      //FrameLengthInBytes = 144 * BitRate / SampleRate + Padding
      $frameLength = 144 * $bitRate * 1000 / $freq + $padding;
    }
    $frameLength = floor($frameLength);
    if ($frameLength == 0)
      return false;
    $seconds += $frameLength*8/($bitRate*1000);
    return array($frameLength,$seconds);
    //Calculate next when next frame starts.
    //Capture next frame.
  }

  function setIdv3_2($track,$title,$artist,$album,$year,$genre,$comments,$composer,$origArtist, $copyright,$url,$encodedBy)
  {
    $urlLength = (int)(strlen($url)+2);
    $copyrightLength = (int)(strlen($copyright)+1);
    $origArtistLength = (int)(strlen($origArtist)+1);
    $composerLength = (int)(strlen($composer)+1);
    $commentsLength = (int)strlen($comments)+5;
    $titleLength = (int) strlen($title)+1;
    $artistLength = (int)strlen($artist)+1;
    $albumLength = (int) strlen($album)+1;
    $genreLength = (int) strlen($genre)+1;
    $encodedByLength = (int)(strlen($encodedBy)+1);
    $trackLength = (int) strlen($track) + 1;
    $yearLength = (int) strlen($year)+1;
    $str .= chr(73);//I
    $str .= chr(68);//D
    $str .= chr(51);//3
    $str .= chr(3);//.
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= chr(8);//.
    $str .= chr(53);//5
    $str .= chr(84);//T
    $str .= chr(82);//R
    $str .= chr(67);//C
    $str .= chr(75);//K
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= chr($trackLength);//.
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= $track;
    $str .= chr(84);//T
    $str .= chr(69);//E
    $str .= chr(78);//N
    $str .= chr(67);//C
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= chr($encodedByLength);//
    $str .= chr(64);//@
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= $encodedBy;
    $str .= chr(87);//W
    $str .= chr(88);//X
    $str .= chr(88);//X
    $str .= chr(88);//X
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= chr($urlLength);//.
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= $url;
    $str .= chr(84);//T
    $str .= chr(67);//C
    $str .= chr(79);//O
    $str .= chr(80);//P
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= chr($copyrightLength);//
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= $copyright;
    $str .= chr(84);//T
    $str .= chr(79);//O
    $str .= chr(80);//P
    $str .= chr(69);//E
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= chr($origArtistLength);//
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= $origArtist;
    $str .= chr(84);//T
    $str .= chr(67);//C
    $str .= chr(79);//O
    $str .= chr(77);//M
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= chr($composerLength);//
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= $composer;
    $str .= chr(67);//C
    $str .= chr(79);//O
    $str .= chr(77);//M
    $str .= chr(77);//M
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= chr($commentsLength);//.
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= chr(9);//
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= $comments;
    $str .= chr(84);//T

    $str .= chr(67);//C
    $str .= chr(79);//O
    $str .= chr(78);//N
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= chr($genreLength);//
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= $genre;
    $str .= chr(84);//T
    $str .= chr(89);//Y
    $str .= chr(69);//E
    $str .= chr(82);//R
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= chr($yearLength);//.
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= $year;
    $str .= chr(84);//T
    $str .= chr(65);//A
    $str .= chr(76);//L
    $str .= chr(66);//B
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= chr($albumLength);//.
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= $album;
    $str .= chr(84);//T
    $str .= chr(80);//P
    $str .= chr(69);//E
    $str .= chr(49);//1
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= chr($artistLength);//
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= $artist;
    $str .= chr(84);//T
    $str .= chr(73);//I
    $str .= chr(84);//T
    $str .= chr(50);//2
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= chr($titleLength);//.
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= chr(0);//
    $str .= $title;
    $this->str = $str.$this->str;
  }

  function mergeBehind(mp3 $mp3)
  {
    $this->str .= $mp3->str;
  }

  function mergeInfront(mp3 $mp3)
  {
    $this->str = $mp3->str.$this->str;
  }

  function getIdvEnd()
  {
    $strlen = strlen($this->str);
    $str = substr($this->str,($strlen-128));
    $str1 = substr($str,0,3);
    if(strtolower($str1) == strtolower('TAG'))
    {
      return $str;
    }
    else
    {
      return false;
    }
  }

  function striptags()
  {
    //Remove start stuff...
    $newStr = '';
    $s = $start = $this->getStart();
    if($s===false)
    {
      return false;
    }
    else
    {
      $this->str = substr($this->str,$start);
    }
    //Remove end tag stuff
    $end = $this->getIdvEnd();
    if($end!==false)
    {
      $this->str = substr($this->str,0,(strlen($this->str)-129));
    }
  }

  function save($path)
  {
    $fp = fopen($path,'w');
    fwrite($fp,$this->str);
    fclose($fp);
  }

  function multiJoin($newpath,$array)
  {
    //join various MP3s
    foreach ($array as $path)
    {
      $mp3 = new mp3($path);
      $mp3->striptags();
      $mp3_1 = new mp3($newpath);
      $mp3->mergeBehind($mp3_1);
      $mp3->save($newpath);
    }
  }

  function cutstr($string,$endlength="30",$end="") 
  {
    $strlen = strlen($string);
    if ($strlen > $endlength) 
    {
      $trim = $endlength-$strlen;
      $string = substr("$string", 0, $trim); 
      $string .= $end;
    }
    return $string;
  }

  function getTags() 
  {
    $tags = array();
    $tag_names = array('Title', 'Artist', 'Album', 'Track');

    $tag_id3v2 = MP3_TAG_ID3v2 ? $this->tag_id3v2() : array();
    $tag_id3v1 = MP3_TAG_ID3v1 ? $this->tag_id3v1() : array();

    foreach($tag_names as $tagitem) 
    {
      $tags[$tagitem] = $this->cutstr($tag_id3v2[$tagitem] ? $tag_id3v2[$tagitem] : $tag_id3v1[$tagitem], 20);
    }
    
    fclose($this->fp);
    return $tags;
  }

  function tag_id3v2() 
  {
    rewind($this->fp);
    $frames = array();

    $headerformat = 'a3Header/C/C/C/CSize0/CSize1/CSize2/CSize3';
    $headersize = 10;

    $headerdata = fread($this->fp, $headersize);
    $header = @unpack($headerformat, $headerdata);

    if(!$header || $header['Header'] != 'ID3') 
    {
      return $frames;
    }

    $tagsize = ($header['Size0'] & 0x7F) * 0x200000
      + ($header['Size1'] & 0x7F) * 0x400
      + ($header['Size2'] & 0x7F) * 0x80
      + ($header['Size3']);

    if(($tagsize = intval($tagsize)) < 1) 
    {
      return $frames;
    }

    $seek_start = ftell($this->fp);
    $seek_end = $seek_start + $tagsize;

    while(1) {

      if(($seek_end - ftell($this->fp)) <= 0) 
      {
        break;
      }

      $frameheaderformat = 'a4FrameID/CSize0/CSize1/CSize2/CSize3/C/C/cCharset';
      $frameheader = @unpack($frameheaderformat, fread($this->fp, 11));

      if(!$frameheader || !$frameheader['FrameID'] || !$frameheader['Size3']) 
      {
        continue;
      }

      $framesize = $frameheader['Size0'] * 0x100000000
        + $frameheader['Size1'] * 0x10000
        + $frameheader['Size2'] * 0x100
        + $frameheader['Size3'];

      if($framesize < 1 || $framesize > $seek_last) {
        continue;
      }

      switch($frameheader['Charset']) 
      {
        case 0: $framecharset = 'ISO-8859-1'; break;
        case 1: $framecharset = 'UTF-16'; break;
        case 2: $framecharset = 'UTF-16BE'; break;
        case 3: $framecharset = 'UTF-8'; break;
        default: continue;
      }

      $framedatasize = $framesize - 1;
      $framedata = @unpack("a{$framedatasize}Data", fread($this->fp, $framedatasize));

      $frames[$frameheader['FrameID']] = MP3_TAG_ICONV ? iconv($framecharset, $GLOBALS['charset'], $framedata) : $framedata;

    }

    return array
      (
      'Title' => $frames['TIT2'],
      'Artist' => $frames['TPE1'],
      'Album' => $frames['TALB'],
      'Track' => $frames['TRCK']
      );
  }

  function tag_id3v1() 
  {
    $tagsize = 128;
    $tagstart = $this->filesize - $tagsize;

    fseek($this->fp, $tagstart);

    $tagformat = 'a3Header/a30Title/a30Artist/a30Album/a4/a28/C/CTrack/C';
    $tag = @unpack($tagformat, fread($this->fp, $tagsize));

    if($tag['Header'] != 'TAG') 
    {
      return array();
    }

    return array
      (
      'Title' => $tag['Title'],
      'Artist' => $tag['Artist'],
      'Album' => $tag['Album'],
      'Track' => $tag['Track']
      );
  }
}
?>