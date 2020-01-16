connect
<?php //plik przechowujacy dane do serwera, oddzielamy pliki
  $host="localhost";
  $db_user="root"; //database user
  $db_password=""; //domyslnie bez hasla root
  $db_name="Hotel";


 ?>
logout
<?php
  session_start();

  session_unset(); //konczenie sesji ( do wylogowywania), nisczy wzysctkie zmienne sutawione w sesji
  header('Location: index.php');

?>
rejestracja
<?php
  session_start();
  require_once "connect.php";
  ?>
  <link rel="stylesheet" href="style.css" type="text/css">
<?php
  mysqli_report(MYSQLI_REPORT_STRICT); //ograniczenie błędów wyswietlanych o tym w jakiej linii jaki błąd ( nie chcemy tego dla uzytkownikow)

  if(isset($_POST['email'])) //sprawdzamy czy istnieje jedna zmienna, bo jesli 1 istanieje to reszta tez, bo powstaeje ta zmienna po submicie
  {
    //udana walidacja :
    $wszystko_OK=true; //jesli bedzie wszystko ok, to ta wertosc pozwoli nam na wyslanie danych do bazy, jak nie to nie wysylamy do bazy
    $login=$_POST['login'];
    //sprawdzenie dlugosci loginu
    if((strlen($login)<3) || (strlen($login)>20))
    {
      $wszystko_OK=false;
      $_SESSION['e_login']="Login musi posiadac od 3 do 20 znakow!";

    }
    //sprawdzanie alfanumeryczne loginu
    if(ctype_alnum($login)==false)
    {
      $wszystko_OK=false;
      $_SESSION['e_login']="Login moze skladac sie tylko z liter i cyfr( bez polskich znakow )";
    }
    //sprawdz poprawnosc email:
    $email=$_POST['email'];
    $emailB=filter_var($email,FILTER_SANITIZE_EMAIL); //filtr do adresów mailowych
    if((filter_var($emailB,FILTER_VALIDATE_EMAIL)==false) || ($emailB!=$email)) //jesli sie zmienil
    {
      $wszystko_OK=false;
      $_SESSION['e_email']="Podaj poprawny email";
    }
    //sprawdz poprawnosc hasla
    $haslo1=$_POST['haslo1'];
    $haslo2=$_POST['haslo2'];
    if((strlen($haslo1)<8) || (strlen($haslo1)>20))
    {
      $wszystko_OK=false;
      $_SESSION['e_haslo']="Haslo musi posiadac miedzy 8 a 20 znakow";
    }
    if($haslo1!=$haslo2)
    {
      $wszystko_OK=false;
      $_SESSION['e_haslo']="Hasla sie roznia";
    }
    //hashowanie
    $haslo_hash=password_hash($haslo1,PASSWORD_DEFAULT);

    //Czy zaakceptowano regulamin?
    if(!isset($_POST['regulamin']))
    {
      $wszystko_OK=false;
      $_SESSION['e_regulamin']="Potwierdz akceptacje regulaminu";
    }

    //Sprawdzanie recaptchy
    $sekret="6LfanSgTAAAAAAHmWXnlqFgch9mslAzZJ5A5oRm0";
    $sprawdz=file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.$sekret.'&response='.$_POST['g-recaptcha-response']);
    $odpowiedz=json_decode($sprawdz);

    if($odpowiedz->success==false)
    {
      $wszystko_OK=false;
      $_SESSION['e_bot']="Potwierdz, ze nie jestes botem";
    }
    //pamietanie wpisywanych danych:
    $_SESSION['stored_login']=$login;
    $_SESSION['stored_email']=$email;
    $_SESSION['stored_haslo1']=$haslo1;
    $_SESSION['stored_haslo2']=$haslo2;
    if(isset($_POST['regulamin'])) $_SESSION['stored_regulamin']=true;





    try // testowanie try catch, do rzucania i łapania błędów i późniejszego wyświetlania // ostatecznie bardziej intuicyjnie robienie $_SESSION['error'] i potem echowanie if isset
     {
        $polaczenie = new mysqli($host,$db_user,$db_password, $db_name);
        if ($polaczenie->connect_errno!=0)
        {
           throw new Exception(mysqli_connect_errno());
        }
        else
        {
          //czy email juz istnieje?
          $rezultat=$polaczenie->query("SELECT nr_id_konta FROM konta WHERE email='$email'");

          if(!$rezultat) throw new Exception($polaczenie->error);

          $ile_takich_maili=$rezultat->num_rows;
          if($ile_takich_maili>0)
          {
            $wszystko_OK=false;
            $_SESSION['e_email']="istnieje juz konto przypisane do tego adresu email";
          }
          //czy login juz istnieje?
          $rezultat=$polaczenie->query("SELECT nr_id_konta FROM konta WHERE login='$login'");

          if(!$rezultat) throw new Exception($polaczenie->error);

          $ile_takich_loginow=$rezultat->num_rows;
          if($ile_takich_loginow>0)
          {
            $wszystko_OK=false;
            $_SESSION['e_login']="istnieje juz konto przypisane do tego loginu";
          }
          // jesli sie udalo ( nie bylo błędów)
            if($wszystko_OK==true)
            {
              //testy zaliczone, dodajemy usera do bazy
              if($polaczenie->query("INSERT INTO `konta`( `pracownik`, `login`, `email`, `hasło`) VALUES ('0','$login','$email','$haslo_hash')"))
              {
                $_SESSION['udana_rejestracja']=true;
                if(isset($_SESSION['zalogowany_pracownik'])) //jesli jestesmy zalogowanym pracownikime
                {
                  header('Location: konto_pracownik.php');
                }
                else {
                  header('Location: witamy.php');
                }

              }
              else {
                throw new Exception ($polaczenie->error);
              }
            }
          $polaczenie->close();
        }
    }
    catch(Exception $e) //łapanie wyjatkow ktore pojawia sie wyzej i pokazywanie ich
    {
      echo '<span style="color:red;">blad serwera! przepraszamy za niedogodnosci i prosimy o rejestracje w innym terminie</span>';
      echo '<br /> Informacja developerska: '.$e; //tutaj tez mozemy zakomentowac aby wiecej info na temat błedów nie było
    }
  }
 ?>

 <!DOCTYPE HTML>
 <html lang="pl">
 <head>
     <meta charset="utf-8" />
     <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
     <title> Rejestracja </title>
     <script src='https://www.google.com/recaptcha/api.js'></script>
     <style>
     .error
     {
        color:red;
        margin-top: 10px;
        margin-bottom: 10px;
     }
     </style>

</head>

<body>
  <div id="container">
        <form method="post">
                                    Login: <br /><input type="text" value="<?php
                                    if(isset($_SESSION['stored_login']))
                                    {
                                      echo $_SESSION['stored_login'];
                                      unset($_SESSION['stored_login']);
                                    }
                                    ?>" name="login" /><br />
                                    <?php
                                    if(isset($_SESSION['e_login']))
                                    {
                                      echo '<div class="error">'.$_SESSION['e_login'].'</div>';
                                      unset($_SESSION['e_login']); // zeby przy nastepnym wyslaniu formularza mozna bylo poprawic bledy
                                    }
                                     ?>
                                    E-mail:<br /><input type="text" value="<?php
                                    if(isset($_SESSION['stored_email']))
                                    {
                                      echo $_SESSION['stored_email'];
                                      unset($_SESSION['stored_email']);
                                    }
                                    ?>" name="email" /><br />
                                    <?php
                                    if(isset($_SESSION['e_email']))
                                    {
                                      echo '<div class="error">'.$_SESSION['e_email'].'</div>';
                                      unset($_SESSION['e_email']); // zeby przy nastepnym wyslaniu formularza mozna bylo poprawic bledy
                                    }
                                     ?>
                                    Hasło: <br /><input type="password" value="<?php
                                    if(isset($_SESSION['stored_haslo1']))
                                    {
                                      echo $_SESSION['stored_haslo1'];
                                      unset($_SESSION['stored_haslo1']);
                                    }
                                    ?>" name="haslo1" /><br />
                                    <?php
                                    if(isset($_SESSION['e_haslo']))
                                    {
                                      echo '<div class="error">'.$_SESSION['e_haslo'].'</div>';
                                      unset($_SESSION['e_haslo']); // zeby przy nastepnym wyslaniu formularza mozna bylo poprawic bledy
                                    }
                                     ?>
                                    Powtórz Hasło: <br /><input type="password" value="<?php
                                    if(isset($_SESSION['stored_haslo2']))
                                    {
                                      echo $_SESSION['stored_haslo2'];
                                      unset($_SESSION['stored_haslo2']);
                                    }
                                    ?>" name="haslo2" /><br />
                                    <?php
                                    if(isset($_SESSION['e_haslo']))
                                    {
                                      echo '<div class="error">'.$_SESSION['e_haslo'].'</div>';
                                      unset($_SESSION['e_haslo']); // zeby przy nastepnym wyslaniu formularza mozna bylo poprawic bledy
                                    }
                                     ?>
                                    <label>
                                      <br /><input type="checkbox" name="regulamin" <?php
                                  if(isset($_SESSION['stored_regulamin']))
                                  {
                                    echo "checked";
                                    unset($_SESSION['stored_regulamin']);
                                  }
                                  ?>/>Akceptuję regulamin hotelu<br />
                                    </label>

                                    <?php
                                    if(isset($_SESSION['e_regulamin']))
                                    {
                                      echo '<div class="error">'.$_SESSION['e_regulamin'].'</div>';
                                      unset($_SESSION['e_regulamin']); // zeby przy nastepnym wyslaniu formularza mozna bylo poprawic bledy
                                    }
                                     ?>

                                    <div class="g-recaptcha" data-sitekey="6LfanSgTAAAAAMsV7YOGr7dOUBGCCvNsEEPSDIxx"></div>
                                    <?php
                                    if(isset($_SESSION['e_bot']))
                                    {
                                      echo '<div class="error">'.$_SESSION['e_bot'].'</div>';
                                      unset($_SESSION['e_bot']); // zeby przy nastepnym wyslaniu formularza mozna bylo poprawic bledy
                                    }
                                     ?>
                                    <br />

                                    <input type="submit" value="zarejestruj sie" />


                </form>
                <?php
                    if(isset($_SESSION['zalogowany_pracownik']))
                    {
                      ?><form action="konto_pracownik.php" method="post">
                        <input type="submit" value="Wróć "></input>
                      <?php
                    }
                    else {
                      ?><form action="index.php" method="post">
                        <input type="submit" value="Wróć "></input>
                      <?php
                    }
                     ?>
                   </div>





</body>
</html>
uzupelnij
<?php
  session_start();
require_once "connect.php";
?>
<link rel="stylesheet" href="style.css" type="text/css">
<?php
error_reporting(E_ERROR);


  $imie=$_POST['imie'];
  $nazwisko=$_POST['nazwisko'];
  $nr_dowodu=$_POST['nr_dowodu'];
  $nr_telefonu=$_POST['nr_telefonu'];
  $jezyk=$_POST['jezyk'];

  $id=$_SESSION['id'];

 // sprawdzanie warunków i łapanie wyjątków
 $polaczenie = @new mysqli($host,$db_user,$db_password,$db_name); //@- wycisza błędy spowodowane przez dalsze instruckje //ustanowienie polaczenia przez mysqli przez przesłanie danych //rezerwuje nowa pamiec, a mysqli to metoda
 $wszystko_OK=true;
 $_SESSION['udane_uzupelnienie']=false;
 if(isset($_POST['imie']))
 {
  try
  {
    //sprawdzanie czy tylko litery ma imie i nazwisko
    if (ctype_alpha($imie) == false || ctype_alpha($nazwisko) == false )
    {
        $wszystko_OK=false;
        $_SESSION['e_imie_nazwisko'] = 'Imie i Nazwisko muszą zawierać tylko litery!';
      }
      //czy nr dowodu juz istnieje?
        $rezultat=$polaczenie->query("SELECT * FROM klienci WHERE nr_dowodu='$nr_dowodu'");

        if(!$rezultat) throw new Exception($polaczenie->error);

        $ile_takich_nr_dowodow=$rezultat->num_rows;
        if($ile_takich_nr_dowodow>0)
        {
          $wszystko_OK=false;
          $_SESSION['e_nr_dowodu']="istnieje juz konto przypisane do tego nr_dowodu!";
        }
        if((strlen($nr_dowodu)!=9))
        {
          $wszystko_OK=false;
          $_SESSION['e_nr_dowodu']="nr dowodu nie jest 9-znakowy!";
        }
        //czy nr telefonu juz istnieje?
        $rezultat=$polaczenie->query("SELECT * FROM klienci WHERE nr_telefonu='$nr_telefonu'");
        $ile_takich_nr_telefonu=$rezultat->num_rows;
       if($ile_takich_nr_telefonu>0)
        {
          $wszystko_OK=false;
          $_SESSION['e_nr_telefonu']="istnieje juz konto przypisane do nr_telefonu!";
        }
        if((strlen($nr_telefonu)!=9))
        {
          $wszystko_OK=false;
        $_SESSION['e_nr_telefonu']="nr_telefonu nie jest 9-znakowy!";
        }
        //sprawdzanie czy tylko ltiery ma jezyk
        if (ctype_alpha($jezyk) == false)
        {
          $_SESSION['e_jezyk'] = 'język musi zawierać tylko litery!';
        }

        $rezultat=$polaczenie->query("SELECT * FROM klienci WHERE imię='$imie' && nazwisko='$nazwisko' ");

        $ile_takich_imion=$rezultat->num_rows;
        if($ile_takich_imion>0)
        {
          $wszystko_OK=false;
          $_SESSION['e_imie_nazwisko']="istnieje juz konto przypisane do tego imienia i nazwiska!";
        }
      }
      catch(Exception $e) //łapanie wyjatkow ktore pojawia sie wyzej i pokazywanie ich
      {
        echo '<span style="color:red;">blad serwera! przepraszamy za niedogodnosci i prosimy o rejestracje w innym terminie</span>';
        echo '<br /> Informacja developerska: '.$e; //tutaj tez mozemy zakomentowac aby wiecej info na temat błedów nie było
      }
      if($wszystko_OK=='true')
      {
        //sprawdzamy czy istnieje juz konto, jesli tak to UPDATE,jesli nie to INSERT INTO, Bo to nie jest tworzenie nowego konta, tylko uzupełnianie danych, takich jak imie nazwisko
        $sql="SELECT * FROM klienci WHERE nr_id_klienta='$id'";
        $rezultat=@$polaczenie->query($sql);
        $czy_istnieje=$rezultat->num_rows;
        if($czy_istnieje==1)
        {
          $polaczenie->query("UPDATE klienci SET imię='$imie',nazwisko='$nazwisko',nr_dowodu='$nr_dowodu',nr_telefonu='$nr_telefonu',język='$jezyk' WHERE nr_id_klienta='$id'");
          $_SESSION['udane_uzupelnienie']=true;
        }
        else{
              $polaczenie->query("INSERT INTO `klienci` (`nr_id_klienta`, `imię`, `nazwisko`, `nr_dowodu`, `nr_telefonu`, `język`) VALUES ('$id', '$imie', '$nazwisko', '$nr_dowodu', '$nr_telefonu', '$jezyk')");
              $_SESSION['udane_uzupelnienie']=true;
        }

      }

   }


   ?>



 <!DOCTYPE HTML>
 <html lang="pl">
 <head>
     <meta charset="utf-8" />
     <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
     <title> Twoje dane </title>
</head>

<body>
  <div id="container">

  <form action="konto.php" method="post">
    <input type="submit" value="Wróć "></input></form>
  <form method="post">
      Imię: <br /> <input type="text" name="imie"/> <br /><br />
      Nazwisko: <br /> <input type="text" name="nazwisko"/> <br /><br />
              <?php
              if(isset($_SESSION['e_imie_nazwisko']))
              {
                echo '<div class="error" style="color:red">'.$_SESSION['e_imie_nazwisko'].'</div>';
                unset($_SESSION['e_imie_nazwisko']); // zeby przy nastepnym wyslaniu formularza mozna bylo poprawic bledy
              }
               ?>
      nr_dowodu<br/>(potrzebny do rezerwacji): <br /> <input type="text" name="nr_dowodu"/> <br /><br />
              <?php
              if(isset($_SESSION['e_nr_dowodu']))
              {
                echo '<div class="error" style="color:red">'.$_SESSION['e_nr_dowodu'].'</div>';
                unset($_SESSION['e_nr_dowodu']); // zeby przy nastepnym wyslaniu formularza mozna bylo poprawic bledy
              }
               ?>
      nr_telefonu: <br /> <input type="text" name="nr_telefonu"/> <br /><br />
              <?php
              if(isset($_SESSION['e_nr_telefonu']))
              {
                echo '<div class="error" style="color:red">'.$_SESSION['e_nr_telefonu'].'</div>';
                unset($_SESSION['e_nr_telefonu']); // zeby przy nastepnym wyslaniu formularza mozna bylo poprawic bledy
              }
               ?>
      język: <br /> <input type="text" name="jezyk"/> <br /><br />
                <?php
                if(isset($_SESSION['e_jezyk']))
                {
                  echo '<div class="error" style="color:red">'.$_SESSION['e_jezyk'].'</div>';
                  unset($_SESSION['e_jezyk']); // zeby przy nastepnym wyslaniu formularza mozna bylo poprawic bledy
                }
                 ?>
      <input type ="submit" value="Zapisz" />


      <?php
      if($_SESSION['udane_uzupelnienie']==true)
      {
        $_SESSION['udane_uzupelnienie']="Udane uzupełnienie danych!";
        echo '<div class="error" style="color:green">'.$_SESSION['udane_uzupelnienie'].'</div>';
        $_SESSION['udane_uzupelnienie']==false;
      }
        unset($_SESSION['e_nr_dowodu']);
        unset($_SESSION['e_nr_telefonu']);
        unset($_SESSION['e_imie_nazwisko']);
        $polaczenie->close();
       ?>
  </form>
</div>

</body>
</html>
witamy:
<?php
  session_start();
  ?>
  <link rel="stylesheet" href="style.css" type="text/css">
<?php
  if(!isset($_SESSION['udana_rejestracja']))
  {
    header('Location:index.php');
    exit(); //nie wykonywać reszty kodu pod spodem, bo i tka przechodzimy do innego pliku .php
  }
  else {
    unset($_SESSION['udana_rejestracja']);
  }

  //usuwamy zmienne sluzace do zapamietania wartosci w formularzu przy nieudanej walidacji danych
  if(isset($_SESSION['stored_login'])) unset($_SESSION['stored_login']);
  if(isset($_SESSION['stored_email'])) unset($_SESSION['stored_email']);
  if(isset($_SESSION['stored_haslo1'])) unset($_SESSION['stored_haslo1']);
  if(isset($_SESSION['stored_haslo2'])) unset($_SESSION['stored_haslo2']);
  if(isset($_SESSION['stored_regulamin'])) unset($_SESSION['stored_regulamin']);
  //usuwanie bledow rejestracji ( z przedrostkiem e)
  if(isset($_SESSION['e_nick'])) unset($_SESSION['e_nick']);
  if(isset($_SESSION['e_email'])) unset($_SESSION['e_email']);
  if(isset($_SESSION['e_haslo'])) unset($_SESSION['e_haslo']);
  if(isset($_SESSION['e_regulamin'])) unset($_SESSION['e_regulamin']);
  if(isset($_SESSION['e_bot'])) unset($_SESSION['e_bot']);

 ?>

 <!DOCTYPE HTML>
 <html lang="pl">
 <head>
     <meta charset="utf-8" />
     <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
     <title> Logowanie </title>
</head>

<body>
  <div id="container">
  <h2>Dziękujemy za rejestracje w serwisie! Możesz już zalogować się na swoje konto! </h2>
  <form action="index.php" method="post">
    <input type="submit" value="Zaloguj "></input>
  <br /><br />
  </div>
</body>
</html>
dane:
<?php
  session_start();
  require_once "connect.php";
  ?>
  <link rel="stylesheet" href="style.css" type="text/css">
<?php
  error_reporting(E_ERROR);


  $id=$_SESSION['id'];
  $polaczenie = @new mysqli($host,$db_user,$db_password,$db_name); //@- wycisza błędy spowodowane przez dalsze instruckje //ustanowienie polaczenia przez mysqli przez przesłanie danych //rezerwuje nowa pamiec, a mysqli to metoda
  $sql="SELECT * FROM klienci WHERE nr_id_klienta='$id'";
  $rezultat=@$polaczenie->query($sql);
  $wiersz=$rezultat->fetch_assoc();
  $sql2="SELECT * FROM konta WHERE nr_id_konta='$id'";
  $rezultat2=@$polaczenie->query($sql2);
  $wiersz2=$rezultat2->fetch_assoc();

 ?>

 <!DOCTYPE HTML>
 <html lang="pl">
 <head>
     <meta charset="utf-8" />
     <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
     <title> Twoje dane </title>
</head>

<body>
  <div id="container">
    <?php
        if(isset($_SESSION['zalogowany_pracownik']))
        {
          ?><form action="konto_pracownik.php" method="post">
            <input type="submit" value="Wróć "></input>
          <?php
        }
        else {
          ?><form action="index.php" method="post">
            <input type="submit" value="Wróć "></input>
          <?php
        }
         ?>
  <br /><br />
  <form method="post">
    Login:    <?php        echo $wiersz2['login']."<br />"; ?>
    Email:    <?php        echo $wiersz2['email']."<br /><br />"; ?>
      Imię:    <?php        echo $wiersz['imię']."<br />"; ?>
      Nazwisko:    <?php        echo $wiersz['nazwisko']."<br />"; ?>
      Nr_dowodu:    <?php        echo $wiersz['nr_dowodu']."<br />"; ?>
      Nr_telefonu:    <?php        echo $wiersz['nr_telefonu']."<br />"; ?>
      Język:    <?php        echo $wiersz['język']."<br />"; ?>

  </form>
</div>
  <?php
    $polaczenie->close();
  ?>

</body>
</html>
konto:
<?php
  session_start();
  ?>
  <link rel="stylesheet" href="style.css" type="text/css">
<?php
  require_once "connect.php"; // bardzo ważne, żeby zrobic potem połączenie i porównywać rzeczy
  $id=$_SESSION['id'];
      $insert=false;
      $delete=false;


  if(isset($_SESSION['zalogowany_pracownik']))
  {
    header('Location konto_pracownik.php');
  }
  else {
    header('Location konto.php');
  }

 ?>

 <!DOCTYPE HTML>
 <html lang="pl">
 <head>
     <meta charset="utf-8" />
     <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
     <title> Konto </title>
</head>

<body>
  <div id="container">
  <?php
          $polaczenie = @new mysqli($host,$db_user,$db_password,$db_name); //@- wycisza błędy spowodowane przez dalsze instruckje //ustanowienie polaczenia przez mysqli przez przesłanie danych //rezerwuje nowa pamiec, a mysqli to metoda
          $date = date('Y-m-d', time());
          $sql99="SELECT * FROM rezerwacje WHERE rezerwacje.data_odjazdu<'$date'";
          $rezultat=@$polaczenie->query($sql99);
          $ile_rezultatow=$rezultat->num_rows; //zbieramy ilosc wystapien w tabeli
          for($i=0;$i<$ile_rezultatow;$i++)
          {
            $wiersz[$i]=$rezultat->fetch_assoc();
            if($date>$wiersz[$i]['data_odjazdu'])
            {
                  $nr_rezerwacji=$wiersz[$i]['nr_rezerwacji'];
                  $nr_id_klienta=$wiersz[$i]['nr_id_klienta'];
                  $nr_pokoju=$wiersz[$i]['nr_pokoju'];
                  $nr_parkingu=$wiersz[$i]['nr_parkingu'];
                  $data_przyjazdu=$wiersz[$i]['data_przyjazdu'];
                  $data_odjazdu=$wiersz[$i]['data_odjazdu'];
                  $cena=$wiersz[$i]['cena'];
                  $oplacone=$wiersz[$i]['opłacone'];


                              $sql98="INSERT INTO `historia_pobytow`(`nr_id_pobytu`, `nr_rezerwacji`, `nr_id_klienta`, `nr_pokoju`, `nr_parkingu`, `data_zameldowania`, `data_wymeldowania`, `cena`, `opłacone`, `timestamp`) VALUES (NULL,'$nr_rezerwacji','$nr_id_klienta','$nr_pokoju','$nr_parkingu','$data_przyjazdu','$data_odjazdu','$cena','$oplacone',NOW())";
                              $rezultat98=@$polaczenie->query($sql98);
                              $sql97="DELETE FROM rezerwacje WHERE '$date'>'$data_odjazdu' ORDER BY rezerwacje.data_odjazdu LIMIT 1 "; //DELETE FROM rezerwacje LIMIT 1 żeby ooprawnie usuwało pojedynczo, bo tak usuwa wszystkie rekordy
                              $rezultat97=@$polaczenie->query($sql97);
                              $polaczenie->query("UPDATE pokoje SET status='wolny' WHERE nr_pokoju='$nr_pokoju' ");
                              $polaczenie->query("UPDATE parkingi SET status='wolne' WHERE miejsce='$nr_parkingu' && status!='brak' ");

                                  $polaczenie->query("DELETE FROM historia_pobytow WHERE historia_pobytow.timestamp <= now() - interval 365 day "); //usuwanie rekorów po 365 dniach !

                                        $sql="UPDATE `rezerwacje` SET `status`='aktywne' WHERE data_odjazdu>'$date' && data_przyjazdu<'$date'";
                                        $sql2="UPDATE pokoje INNER JOIN rezerwacje ON rezerwacje.nr_pokoju=pokoje.nr_pokoju  SET pokoje.status='aktywne' WHERE  rezerwacje.data_odjazdu>'$date' && rezerwacje.data_przyjazdu<'$date' ";
                                        $sql3="UPDATE parkingi INNER JOIN rezerwacje ON rezerwacje.nr_parkingu=parkingi.id_parkingu  SET parkingi.status='aktywne' WHERE  rezerwacje.data_odjazdu>'$date' && rezerwacje.data_przyjazdu<'$date'";
                                        $rezultat=@$polaczenie->query($sql);
                                        $rezultat=@$polaczenie->query($sql2);
                                        $rezultat=@$polaczenie->query($sql3);

                                        $sql="UPDATE `rezerwacje` SET `status`='rezerwacja' WHERE data_przyjazdu>'$date'";
                                        $rezultat=@$polaczenie->query($sql); // komendy mozna również w wielu linijkach tak jak niżej
                                        $sql="UPDATE pokoje SET status = 'wolny'
                                        FROM pokoje
                                        INNER JOIN rezerwacje
                                        on pokoje.nr_pokoju = rezerwacje.nr_pokoju
                                        WHERE rezerwacje.nr_id_konta='$id'
                                        ";
                                        $rezultat=@$polaczenie->query($sql); // inny sposób, zamiast $sql i potem ->query($sql ), można od razu w jednej linijce


                  }
            }
// login z sesji jest przesyłany przez identyfikator sesji przekazywany metodą get w ciasteczkach
?>     <form action="logout.php" method="get">
     <input type="submit" value="Wyloguj się"></input></form>
     <?php    echo "<h3>Witaj użytkowniku ".$_SESSION['login']."</h3>";?>

   <form action="dane.php" method="get">
   <input type="submit" value="Zobacz swoje dane"></input></form>
   <form action="uzupelnij.php" method="get">
   <input type="submit" value="Uzupełnij swoje dane"></input></form>
   <form action="zmien.php" method="get">
   <input type="submit" value="Zmień Swoje Dane"></input></form>
   <form action="historia_rezerwacji.php" method="get">
   <input type="submit" value="Przeglądaj rezerwacje"></input></form>
   <form action="zarezerwuj.php" method="get">
   <input type="submit" value="Zrób rezerwację"></input></form>

   <?php
      $polaczenie->close();
    ?>
</div>
<h1>
<p><br />Zdjęcie nie przedstawia prawdziwego hotelu , źródło: https://q-cf.bstatic.com/images/hotel/max1024x768/797/79726354.jpg</p>
</h1>
</body>
</html>
zmien:
<?php
  session_start();
  require_once "connect.php"; // bardzo ważne, żeby zrobic potem połączenie i porównywać rzeczy
  ?>
  <link rel="stylesheet" href="style.css" type="text/css">
  <?php
  error_reporting(E_ERROR);
  $id=$_SESSION['id'];

  $polaczenie = @new mysqli($host,$db_user,$db_password,$db_name); //@- wycisza błędy spowodowane przez dalsze instruckje //ustanowienie polaczenia przez mysqli przez przesłanie danych //rezerwuje nowa pamiec, a mysqli to metoda

  $_SESSION['stored_login']=$_POST['login'];
  $_SESSION['stored_email']=$_POST['email'];

  $wszystko_OK=true;
  $_SESSION['udana_zmiana']=false;
  if(isset($_POST['login'])) //sprawdzamy czy istnieje jedna zmienna, bo jesli 1 istanieje to reszta tez, bo powstaeje ta zmienna po submicie, a jesli istnieje to mozemy dalej robic polecenia, bez tego są wykonywane wcześniej i wyświetla się "pkoj zajety", a to było nieładne
  {
    try
    {
      //udana walidacja :
       //jesli bedzie wszystko ok, to ta wertosc pozwoli nam na wyslanie danych do bazy, jak nie to nie wysylamy do bazy
      $login=$_POST['login'];
      //sprawdzenie dlugosci loginu
      if((strlen($login)<3) || (strlen($login)>20))
      {
        $wszystko_OK=false;
        $_SESSION['e_login']="Login musi posiadac od 3 do 20 znakow!";
      }
      //sprawdzanie alfanumeryczne loginu
      if(ctype_alnum($login)==false)
      {
        $wszystko_OK=false;
        $_SESSION['e_login']="Login moze skladac sie tylko z liter i cyfr( bez polskich znakow )";
      }
      //sprawdz poprawnosc email:
      $email=$_POST['email'];
      $emailB=filter_var($email,FILTER_SANITIZE_EMAIL); //filtr do adresów mailowych
      if((filter_var($emailB,FILTER_VALIDATE_EMAIL)==false) || ($emailB!=$email)) //jesli sie zmienil
      {
        $wszystko_OK=false;
        $_SESSION['e_email']="Podaj poprawny email";
      }
      //sprawdz poprawnosc hasla
      $haslo1=$_POST['haslo1'];
      $haslo2=$_POST['haslo2'];
      if((strlen($haslo1)<8) || (strlen($haslo1)>20))
      {
        $wszystko_OK=false;
        $_SESSION['e_haslo']="Haslo musi posiadac miedzy 8 a 20 znakow";
      }
      if($haslo1!=$haslo2)
      {
        $wszystko_OK=false;
        $_SESSION['e_haslo']="Hasla sie roznia";
      }
      //hashowanie
      $haslo_hash=password_hash($haslo1,PASSWORD_DEFAULT);

        if ($polaczenie->connect_errno!=0)
        {
           throw new Exception(mysqli_connect_errno());
        }
        else
        {
          //czy email juz istnieje?
          $rezultat=$polaczenie->query("SELECT nr_id_konta FROM konta WHERE email='$email'");

          if(!$rezultat) throw new Exception($polaczenie->error);

          $ile_takich_maili=$rezultat->num_rows;
          if($ile_takich_maili>0)
          {
            $wszystko_OK=false;
            $_SESSION['e_email']="istnieje juz konto przypisane do tego adresu email";
          }
          //czy login juz istnieje?
          $rezultat=$polaczenie->query("SELECT nr_id_konta FROM konta WHERE login='$login'");

          if(!$rezultat) throw new Exception($polaczenie->error);

          $ile_takich_loginow=$rezultat->num_rows;
          if($ile_takich_loginow>0)
          {
            $wszystko_OK=false;
            $_SESSION['e_login']="istnieje juz konto przypisane do tego loginu";
          }
          // jesli sie udalo ( nie bylo błędów)

        }
    }
    catch(Exception $e) //łapanie wyjatkow ktore pojawia sie wyzej i pokazywanie ich
    {
      echo '<span style="color:red;">blad serwera! przepraszamy za niedogodnosci i prosimy o rejestracje w innym terminie</span>';
      echo "<br />";
      #echo '<br /> Informacja developerska: '.$e; //tutaj tez mozemy zakomentowac aby wiecej info na temat błedów nie było, takie info tylko dla devow
    }
    if($wszystko_OK=='true')
    {
      //testy zaliczone, dodajemy usera do bazy
      if($polaczenie->query(" UPDATE konta SET login='$login', email='$email', hasło='$haslo_hash' WHERE nr_id_konta='$id' "))
      {
        $_SESSION['udana_zmiana']=true;
      }
    }
  }
 ?>

 <!DOCTYPE HTML>
 <html lang="pl">
 <head>
     <meta charset="utf-8" />
     <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
     <title> Zmiana loginu/hasła/email </title>
</head>

<body>
  <div id="container">
    <?php
        if(isset($_SESSION['zalogowany_pracownik']))
        {
          ?><form action="konto_pracownik.php" method="post">
            <input type="submit" value="Wróć "></input>
          <?php
        }
        else {
          ?><form action="index.php" method="post">
            <input type="submit" value="Wróć "></input>
          <?php
        }
         ?>
</form>
  <form method="post">
    <br /><br />
  Nowy login: <br /><input type="text" value="<?php session_start();
  if(isset($_SESSION['stored_login']))
  {
    echo $_SESSION['stored_login'];
    unset($_SESSION['stored_login']);
  }
  ?>" name="login" /><br /><br />
  <?php
  if(isset($_SESSION['e_login']))
  {
    echo '<div class="error" style="color:red">'.$_SESSION['e_login'].'</div>';
    unset($_SESSION['e_login']); // zeby przy nastepnym wyslaniu formularza mozna bylo poprawic bledy
  }
   ?>
Nowy email: <br /><input type="text" value="<?php
if(isset($_SESSION['stored_email']))
{
echo $_SESSION['stored_email'];
unset($_SESSION['stored_email']);
}
?>" name="email" /><br /><br />
 <?php
 if(isset($_SESSION['e_email']))
 {
   echo '<div class="error" style="color:red">'.$_SESSION['e_email'].'</div>';
   unset($_SESSION['e_email']); // zeby przy nastepnym wyslaniu formularza mozna bylo poprawic bledy
 }
  ?>

  Nowe hasło: <br /><input type="password" name="haslo1" /><br /><br />
  Powtórz nowe hasło: <br /><input type="password" name="haslo2" /><br /><br />
                <?php
                if(isset($_SESSION['e_haslo']))
                {
                  echo '<div class="error" style="color:red">'.$_SESSION['e_haslo'].'</div>';
                  unset($_SESSION['e_haslo']); // zeby przy nastepnym wyslaniu formularza mozna bylo poprawic bledy
                }
                 ?>

<br /><br />
  <input type="submit" value="Zaktualizuj" />

  <?php
  if($_SESSION['udana_zmiana']==true)
  {
    $_SESSION['udana_zmiana']="Udana zmiana danych!";
    echo '<div class="error" style="color:green">'.$_SESSION['udana_zmiana'].'</div>';
    unset($_SESSION['e_haslo']); // zeby przy nastepnym wyslaniu formularza mozna bylo poprawic bledy
  }
    unset($_SESSION['e_haslo']);
    unset($_SESSION['e_login']);
    unset($_SESSION['e_email']);
    $polaczenie->close();
   ?>
</form>
</div>

</body>
</html>

zarezerwuj_pracownik<?php

session_start();

?>
<link rel="stylesheet" href="style.css" type="text/css">
<?php
if(!isset($_SESSION['zalogowany_pracownik']))
{
  header('Location:konto.php');
}
error_reporting(E_ERROR);
require_once "connect.php";

$imie=$_POST['imie'];
$imie= htmlentities($imie, ENT_QUOTES, "UTF-8"); //przeciwdziałanie SQL INJECTION !
$nazwisko=$_POST['nazwisko'];
$nazwisko= htmlentities($nazwisko, ENT_QUOTES, "UTF-8");

$nr_pokoju=$_POST['nr_pokoju'];
$miejsce_parkingowe=$_POST['miejsce_parkingowe'];
$miejsce_parkingowe= htmlentities($miejsce_parkingowe, ENT_QUOTES, "UTF-8");
$data_przyjazdu=$_POST['data_przyjazdu'];
$data_odjazdu=$_POST['data_odjazdu'];

$poprawnosc=0; //sprawdza poprawnosc pokojów i parkingów
$p=0; //do inkrementacji elementu w tablicy tabela
$b=0; //do inkrementacji elementu w tablicy tabela2
$tabela[22]=[]; //tabela przechowująca wolne pokoje
$tabela2[19]=[]; //tabela przechowujaca wolne parkingi
$date = date('Y-m-d', time());


 ?>

<!DOCTYPE HTML>
<html lang="pl">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <title> Zarezerwuj pokój </title>
</head>

<body>
  <div id="container">
 <?php
     if(isset($_SESSION['zalogowany_pracownik']))
     {
       ?><form action="konto_pracownik.php" method="post">
         <input type="submit" value="Wróć "></input>
       <?php
     }
     else {
       ?><form action="index.php" method="post">
         <input type="submit" value="Wróć "></input>
       <?php
     }
      ?>
    </form>

<?php
        session_start();
            if(isset($_POST['potwierdz']))
            {

            $polaczenie = @new mysqli($host,$db_user,$db_password,$db_name); //@- wycisza błędy spowodowane przez dalsze instruckje //ustanowienie polaczenia przez mysqli przez przesłanie danych //rezerwuje nowa pamiec, a mysqli to metoda
            #$imie=$_POST['imie'];
            #$nazwisko=$_POST['nazwisko'];
            $sql="SELECT * FROM klienci WHERE '$imie'=klienci.imię && '$nazwisko'=klienci.nazwisko ";
            $rezultat=@$polaczenie->query($sql);
            $wiersz = $rezultat->fetch_assoc();
            $ile_rezultatow=$rezultat->num_rows; //zbieramy ilosc wystapien w tabeli
            if($ile_rezultatow=='0')
            {
              $_SESSION['error_rezultatow']="Nie znaleziono uzytkownika o takim imieniu i nazwisku!";
            }
            else {
              unset($_SESSION['error_rezultatow']); // zeby przy nastepnym wyslaniu formularza mozna bylo poprawic bledy
              $_SESSION['brak_error_rezultatow']="Poprawny użytkownik!";
              $poprawnosc++;
              $_SESSION['poprawnosc']++;
              $id=$wiersz['nr_id_klienta'];

            }
          }

?>
<?php
 ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
 ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
 ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
 //                                                    TA CZĘŚĆ ODPOWIADA ZA PODAWANIE IMIENIA I NAZWISKA
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        ?>
        <form method="post">

                       Podaj imię:<br />
                       <input type="text" name="imie" value="<?php echo $imie; ?>" /><br />
                       Podaj nazwisko:<br />
                           <input type="text" name="nazwisko" value="<?php echo $nazwisko; ?> " /><br /><br />
                           <?php
                           if(isset($_SESSION['error_rezultatow']))
                           {
                             echo '<div class="error" style="color:red">'.$_SESSION['error_rezultatow'].'</div>';
                             unset($_SESSION['error_rezultatow']);
                             ?><a href ="zarezerwuj.php">Wprowadź poprawnego użytkownika</a><?php
                             exit();

                           }
                           if(isset($_SESSION['brak_error_rezultatow']))
                           {
                             echo '<div class="error" style="color:green">'.$_SESSION['brak_error_rezultatow'].'</div>';
                             ?><?php

                           }
                            ?>
                            <input type="submit" name="potwierdz" value="Zweryfikuj klienta" />

                          <?php
                          session_start(); // rozpoczynanie sesji w każdym paragrafie php bo inaczej były błędy, nie przechowywałlo między sesjami wszystkich zmiennych prawidłowo!
                          echo "<br />";

                            if (($data_odjazdu < $data_przyjazdu && $data_przyjazdu>=$date && $data_odjazdu>=$date ) || ( $data_odjazdu > $data_przyjazdu && $data_przyjazdu<=$date && $data_odjazdu<=$date ) || ( $data_odjazdu < $data_przyjazdu && $data_przyjazdu<=$date && $data_odjazdu<=$date ) || ( $data_odjazdu > $data_przyjazdu && $data_przyjazdu<=$date && $data_odjazdu>=$date ) || ( $data_odjazdu < $data_przyjazdu && $data_przyjazdu>=$date && $data_odjazdu<=$date ))
                                  {
                                    $_SESSION['e_daty']="Data przyjazdu nie może być później niż odjazdu!";
                                  } ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                                   ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                                   ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                                   //                                                    TA CZĘŚĆ ODPOWIADA ZA PODAWANIE DATY
                                  ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                                  ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                                 ?>
                                                    <br />
                                         Data przyjazdu: <br /><input type="date" name="data_przyjazdu" value=<?php echo $data_przyjazdu; ?> min="2020-01-01" max="2020-12-30"/><br />
                                         Data odjazdu: <br /><input type="date" name="data_odjazdu" value=<?php echo $data_odjazdu; ?> min="2020-01-01" max="2020-12-30"/><br /><br />
                                                   <?php
                                                   session_start();
                                                   if(isset($_SESSION['e_daty']) ) // jesli był błąd w podawaniu daty
                                                   {
                                                     echo '<div class="error" style="color:red">'.$_SESSION['e_daty'].'</div>';
                                                     unset($_SESSION['e_daty']); // zeby przy nastepnym wyslaniu formularza mozna bylo poprawic bledy
                                                     ?><a href ="zarezerwuj.php">Wybierz poprawną datę</a><?php
                                                     exit();
                                                     ?>
                                                     <?php

                                                   }?>
                                                   <input type="submit" value="Zweryfikuj date" name="submit"/>
                                                  <br />
                                                  <br />


        <?php
        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //                                                    TA CZĘŚĆ ODPOWIADA ZA AKCJE PO NACIŚNIĘCIU WERYFIKACJI DATY ( NAJPIERW WYŚWIETLANIE WOLNYCH POKOJÓW)
       ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
       ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        session_start();
        if(isset($_POST['submit']) ) //jeśli nacisnięto submit
        {

          echo "Wolne pokoje:<br />";
        $polaczenie = @new mysqli($host,$db_user,$db_password,$db_name);
        echo "<br />";
        $date = date('Y-m-d a', time());

        $sql="SELECT rezerwacje.nr_id_klienta,pokoje.nr_pokoju,pokoje.rodzaj_pokoju,pokoje.cena FROM pokoje,rezerwacje
        WHERE  (rezerwacje.nr_pokoju = pokoje.nr_pokoju && rezerwacje.status!='aktywny' && (('$data_przyjazdu'>rezerwacje.data_odjazdu && '$data_odjazdu'>rezerwacje.data_odjazdu) || ('$data_przyjazdu'<rezerwacje.data_przyjazdu && '$data_odjazdu'<rezerwacje.data_przyjazdu) )) ";
        $rezultat=@$polaczenie->query($sql);
        $ile_rezultatow=$rezultat->num_rows; //zbieramy ilosc wystapien w tabeli
        for($i=0; $i<$ile_rezultatow; $i++)
        {
          $wiersz[$i]=$rezultat->fetch_assoc();
          // sprawdzanie czy numer pokoju się powtarza, bo jeśli się powtarzał to potrafiło wyświetlić numer pokoju dostępny, bo np była późniejsza rezerwacja i jak sprawdzaliśmy wcześniejszą datą, to ona przechodziła nasz warunel - daty wcześniejsze , więc sprawdzanie ile razy wystąpiło i wyświetlanie tylko wtedy gdy ten pokój spełnia warunek ZAWSZE, TYLE RAZY ILE KWERENDA OBLICZY
          $test=$wiersz[$i]['nr_pokoju'];
          $zliczenia=0;
          $wyswietlone=0;
          $sql_liczba="SELECT * FROM rezerwacje WHERE $test=rezerwacje.nr_pokoju";
          $rezultat_liczby=@$polaczenie->query($sql_liczba);
          $ile_rezultatow_liczby=$rezultat_liczby->num_rows;

          for($x=0;$x<$ile_rezultatow;$x++)
          {
            if($wiersz[$x]['nr_pokoju']==$test)
              {
                  $zliczenia++;
              }
            if($ile_rezultatow_liczby==$zliczenia)
            {
              $zliczenia=0;
              if($nr_pokoju=$test)
              {
                echo "Pokój numer: ".$wiersz[$i]['nr_pokoju']." : ".$wiersz[$i]['rodzaj_pokoju']." , cena:  ".$wiersz[$i]['cena']."<br />";
              }

            }
          }
        }///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //                                                    TERAZ WYŚWIETLANIE WOLNYCH PARKINGÓW
       ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
       ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

                echo "<br /><br />";
                echo "---------------------------------------<br />";
                echo "Wolne miejsca parkingowe:<br /><br />";
                $sql="SELECT rezerwacje.nr_parkingu,parkingi.miejsce, parkingi.id_parkingu,rezerwacje.data_przyjazdu,rezerwacje.data_odjazdu,rezerwacje.status,parkingi.cena FROM parkingi,rezerwacje
                WHERE  (rezerwacje.nr_parkingu = parkingi.id_parkingu && rezerwacje.status!='aktywny' && parkingi.status!='brak' && (('$data_przyjazdu'>rezerwacje.data_odjazdu && '$data_odjazdu'>rezerwacje.data_odjazdu) || ('$data_przyjazdu'<rezerwacje.data_przyjazdu && '$data_odjazdu'<rezerwacje.data_przyjazdu) )  )";
                $rezultat=@$polaczenie->query($sql);
                $ile_rezultatow=$rezultat->num_rows; //zbieramy ilosc wystapien w tabeli
                for($i=0; $i<$ile_rezultatow; $i++)
                {
                  $wiersz[$i]=$rezultat->fetch_assoc();
                  // sprawdzanie czy numer pokoju się powtarza, bo jeśli się powtarzał to potrafiło wyświetlić numer pokoju dostępny, bo np była późniejsza rezerwacja i jak sprawdzaliśmy wcześniejszą datą, to ona przechodziła nasz warunel - daty wcześniejsze , więc sprawdzanie ile razy wystąpiło i wyświetlanie tylko wtedy gdy ten pokój spełnia warunek ZAWSZE, TYLE RAZY ILE KWERENDA OBLICZY
                  $test=$wiersz[$i]['nr_parkingu'];
                  $zliczenia=0;
                  $wyswietlone=0;
                  $sql_liczba="SELECT * FROM rezerwacje WHERE $test=rezerwacje.nr_parkingu";
                  $rezultat_liczby=@$polaczenie->query($sql_liczba);
                  $ile_rezultatow_liczby=$rezultat_liczby->num_rows;

                  for($x=0;$x<$ile_rezultatow;$x++)
                  {
                    if($wiersz[$x]['nr_parkingu']==$test)
                      {
                          $zliczenia++;
                      }
                    if($ile_rezultatow_liczby==$zliczenia)
                    {
                      $zliczenia=0;
                        echo "Parking numer: ".$wiersz[$i]['miejsce']." cena: ".$wiersz[$i]['cena']." <br />";
                    }
                  }

                }

          }
                 ?>
                            Wybierz numer pokoju: <br /><input type="text" name="nr_pokoju"/><br />
                            Wybierz numer miejsca parkingowego: <br /><input type="text" name="miejsce_parkingowe"/><br />
                           <input type="submit" value="Rezerwuj" name="submit2"/>
                 </form>

<br />



                        <?php
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //TA CZĘŚĆ ODPOWIADA ZA AKCJE PO NACIŚNIĘCIU REZERWUJ -- czyli sprawdzenie  czy pokój znajduje siee ( i zliczanie ile razy, aby w przypadku wielokrotnego wystapienia wziac pod uwage ze moze byc pokoj niedostepny w terminie oraz ten sam pokoj dostepnym w innym ) w tablicy która zbierała wolne pokoje i to samo z parkingami
   ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
   ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                        session_start();
                        if(isset($_POST['submit2']))
                        {

                          $polaczenie = @new mysqli($host,$db_user,$db_password,$db_name); //@- wycisza błędy spowodowane przez dalsze instruckje //ustanowienie polaczenia przez mysqli przez przesłanie danych //rezerwuje nowa pamiec, a mysqli to metoda
                          $sql="SELECT rezerwacje.nr_id_klienta,pokoje.nr_pokoju,pokoje.rodzaj_pokoju,pokoje.cena FROM pokoje,rezerwacje
                          WHERE  (rezerwacje.nr_pokoju = pokoje.nr_pokoju && rezerwacje.status!='aktywny' && (('$data_przyjazdu'>rezerwacje.data_odjazdu && '$data_odjazdu'>rezerwacje.data_odjazdu) || ('$data_przyjazdu'<rezerwacje.data_przyjazdu && '$data_odjazdu'<rezerwacje.data_przyjazdu) )) ";
                          $rezultat=@$polaczenie->query($sql);
                          $ile_rezultatow=$rezultat->num_rows; //zbieramy ilosc wystapien w tabeli
                          for($i=0; $i<$ile_rezultatow; $i++)
                          {
                              $wiersz[$i]=$rezultat->fetch_assoc();
                              $test=$wiersz[$i]['nr_pokoju'];
                              $zliczenia=0;
                              $wyswietlone=0;
                              $sql_liczba="SELECT * FROM rezerwacje WHERE $test=rezerwacje.nr_pokoju";
                              $rezultat_liczby=@$polaczenie->query($sql_liczba);
                              $ile_rezultatow_liczby=$rezultat_liczby->num_rows;

                              for($x=0;$x<$ile_rezultatow;$x++)
                              {
                                if($wiersz[$x]['nr_pokoju']==$test)
                                  {
                                      $zliczenia++;
                                  }
                                if($ile_rezultatow_liczby==$zliczenia)
                                {
                                  $zliczenia=0;
                                  if($nr_pokoju=$test)
                                  {
                                    $tabela[$b]=$wiersz[$i]['nr_pokoju'];
                                    $b++;
                                  }

                                }
                              }
                            }
                              $nr_pokoju=$_POST['nr_pokoju'];
                              $sql="SELECT rezerwacje.nr_parkingu,parkingi.miejsce, parkingi.id_parkingu,rezerwacje.data_przyjazdu,rezerwacje.data_odjazdu,rezerwacje.status,parkingi.cena FROM parkingi,rezerwacje
                              WHERE  (rezerwacje.nr_parkingu = parkingi.id_parkingu && rezerwacje.status!='aktywny' && (('$data_przyjazdu'>rezerwacje.data_odjazdu && '$data_odjazdu'>rezerwacje.data_odjazdu) || ('$data_przyjazdu'<rezerwacje.data_przyjazdu && '$data_odjazdu'<rezerwacje.data_przyjazdu) )  )";
                              $rezultat=@$polaczenie->query($sql);
                              $ile_rezultatow=$rezultat->num_rows; //zbieramy ilosc wystapien w tabeli
                              for($i=0; $i<$ile_rezultatow; $i++)
                              {
                                $wiersz[$i]=$rezultat->fetch_assoc();
                                // sprawdzanie czy numer pokoju się powtarza, bo jeśli się powtarzał to potrafiło wyświetlić numer pokoju dostępny, bo np była późniejsza rezerwacja i jak sprawdzaliśmy wcześniejszą datą, to ona przechodziła nasz warunel - daty wcześniejsze , więc sprawdzanie ile razy wystąpiło i wyświetlanie tylko wtedy gdy ten pokój spełnia warunek ZAWSZE, TYLE RAZY ILE KWERENDA OBLICZY
                                $test=$wiersz[$i]['nr_parkingu'];
                                $zliczenia=0;
                                $wyswietlone=0;
                                $sql_liczba="SELECT * FROM rezerwacje WHERE $test=rezerwacje.nr_parkingu";
                                $rezultat_liczby=@$polaczenie->query($sql_liczba);
                                $ile_rezultatow_liczby=$rezultat_liczby->num_rows;

                                for($x=0;$x<$ile_rezultatow;$x++)
                                {
                                  if($wiersz[$x]['nr_parkingu']==$test)
                                    {
                                        $zliczenia++;
                                    }
                                  if($ile_rezultatow_liczby==$zliczenia)
                                  {
                                    $zliczenia=0;
                                      $tabela2[$p]=$wiersz[$i]['miejsce'];
                                      $p++;
                                  }
                                }

                              }


                         // sprawdzanie poprawnosci pokojow
                         $rozmiar=count($tabela)+1; // pobieranie wielkości tabeli +1, bo idexowanie od 0
                         $nr_pokoju2=$nr_pokoju;
                         for($i=0;$i<$rozmiar+1;$i++)
                         {
                           if ($nr_pokoju2==$tabela[$i])
                           {
                             $poprawnosc++;
                           }
                         }
                         if($poprawnosc>'1') // jesli sie powtórzył pokoj, to zostaw przy wartości 1, bo to wartość która weryfikuje pokoj jako dostepny
                         {
                            $poprawnosc=1;
                         }
                         $rozmiar2=count($tabela2)+1;
                         $nr_parkingu2=$miejsce_parkingowe;
                         for($i=0;$i<$rozmiar2+1;$i++)
                         {
                           if($nr_parkingu2==$tabela2[$i])
                           {
                             $poprawnosc++;
                           }
                         }
                         if($poprawnosc>'2') // jesli sie powtórzył parking, to zostaw przy wartości 2, bo to wartość która weryfikuje parking jako dostepny
                         {
                             $poprawnosc=2;
                         }
                                  //sumujemy ceny za pokój i parking
                                  $sql="SELECT * FROM pokoje WHERE nr_pokoju='$nr_pokoju'";
                                  $rezultat=@$polaczenie->query($sql);
                                  $wiersz=$rezultat->fetch_assoc();
                                  $cena1=$wiersz['cena'];
                                    $sql="SELECT * FROM parkingi WHERE miejsce='$miejsce_parkingowe'";
                                    $rezultat=@$polaczenie->query($sql);
                                    $wiersz=$rezultat->fetch_assoc();
                                    $cena2=$wiersz['cena'];
                                      $cena3=$cena1+$cena2;
                                          $sql="SELECT * FROM klienci WHERE '$imie'=klienci.imię && '$nazwisko'=klienci.nazwisko ";
                                          $rezultat=@$polaczenie->query($sql);
                                          $wiersz = $rezultat->fetch_assoc();
                                            $id=$wiersz['nr_id_klienta'];

if($poprawnosc=='2')
{
      unset($_SESSION['brak_error_rezultatow']);
      if($miejsce_parkingowe==0) // można zarezerwaować bez parkingu, wtedy pole zostawiamy puste, i wysyłamy do tabeli w mysql z index 0 jako brak miejsca
      {
        $polaczenie->query("INSERT INTO `rezerwacje`( `nr_id_klienta`, `nr_pokoju`, `nr_parkingu`, `data_przyjazdu`, `data_odjazdu`, `cena`, `opłacone`, `status`) VALUES ( '$id', '$nr_pokoju', '0', '$data_przyjazdu', '$data_odjazdu', '$cena3', 'tak','tak')");
      } // tu nie potrzeba else, bo jesli nie ma miejsca parkingowego to i tak poniższa komenda nie przejdzie                                                                                              # tutaj
        $polaczenie->query("INSERT INTO `rezerwacje`( `nr_id_klienta`, `nr_pokoju`, `nr_parkingu`, `data_przyjazdu`, `data_odjazdu`, `cena`, `opłacone`, `status`) VALUES ( '$id', '$nr_pokoju','$miejsce_parkingowe', '$data_przyjazdu', '$data_odjazdu', '$cena3', 'tak','tak')");
        unset($_SESSION['error']); // wyciszam error, bo jak sie wypełni date i potem submituje to po wypełnieniu pokoju i parkingu nie pamięta że data jest wybrana
        $_SESSION['success']="Rezerwacja udana";
                                                                                        }
          else {
          if(isset($_POST['submit2']))
          {
                $_SESSION['not_ok']=" Podałeś zły numer pokoju i parkingu";
                echo '<div class="error" style="color:RED">'.$_SESSION['not_ok'].'</div>';
                unset($_SESSION['not_ok']); // zeby przy nastepnym wyslaniu formularza mozna bylo poprawic ble
                exit();
          }
          }
$polaczenie->close();
}


?>


<?php

if(isset($_SESSION['success']))
{
  echo '<div class="error" style="color:green">'.$_SESSION['success'].'</div>';
  unset($_SESSION['success']); // zeby przy nastepnym wyslaniu formularza mozna bylo poprawic bledy
}

  unset($_SESSION['blad']);
  unset($_SESSION['e_status_pokoj']);
  unset($_SESSION['error']);
  unset($_SESSION['error_rezultatow']);

 ?>
</div>
</body>
</html>

historia rezerwacji
<?php
session_start();

?>
<link rel="stylesheet" href="style.css" type="text/css">
<?php
if(!isset($_SESSION['zalogowany_pracownik']))
{
  header('Location:konto.php');
}
error_reporting(E_ERROR);
require_once "connect.php";
$imie=$_POST['imie'];
$imie= htmlentities($imie, ENT_QUOTES, "UTF-8");

$nazwisko=$_POST['nazwisko'];
$nazwisko= htmlentities($nazwisko, ENT_QUOTES, "UTF-8");
  session_start();
  require_once "connect.php";
  $id=$_SESSION['id'];
  ?>

 <!DOCTYPE HTML>
 <html lang="pl">
 <head>
     <meta charset="utf-8" />
     <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
     <title> Historia Rezerwacji </title>
</head>

<body>
  <div id="container">
      <?php
          if(isset($_SESSION['zalogowany_pracownik']))
          {
            ?><form action="konto_pracownik.php" method="post">
              <input type="submit" value="Wróć "></input>
            <?php
          }
          else {
            ?><form action="index.php" method="post">
              <input type="submit" value="Wróć "></input>
            <?php
          }
           ?>
      <form method="post">


  </form>

            <?php

            session_start();
                if(isset($_POST['potwierdz']))
                {
                $polaczenie = @new mysqli($host,$db_user,$db_password,$db_name); //@- wycisza błędy spowodowane przez dalsze instruckje //ustanowienie polaczenia przez mysqli przez przesłanie danych //rezerwuje nowa pamiec, a mysqli to metoda
                $sql="SELECT * FROM klienci WHERE '$imie'=klienci.imię && '$nazwisko'=klienci.nazwisko ";
                $rezultat=@$polaczenie->query($sql);
                $wiersz = $rezultat->fetch_assoc();
                $ile_rezultatow=$rezultat->num_rows; //zbieramy ilosc wystapien w tabeli
                if($ile_rezultatow=='0')
                {
                  $_SESSION['error_rezultatow']="Nie znaleziono uzytkownika o takim imieniu i nazwisku!";
                }
                else {
                  unset($_SESSION['error_rezultatow']); // zeby przy nastepnym wyslaniu formularza mozna bylo poprawic bledy
                  $_SESSION['brak_error_rezultatow']="Poprawny użytkownik!<br />";
                  $poprawnosc++;
                  $_SESSION['poprawnosc']++;
                  $id=$wiersz['nr_id_klienta'];

                }
              }

            ?>

<form method="post">

               Podaj imię:<br />
               <input type="text" name="imie" value="<?php echo $imie; ?>" /><br />
               Podaj nazwisko:<br />
                   <input type="text" name="nazwisko" value="<?php echo $nazwisko; ?> " /><br /><br />
                   <?php
                   if(isset($_SESSION['error_rezultatow']))
                   {
                     echo '<div class="error" style="color:red">'.$_SESSION['error_rezultatow'].'</div>';
                     unset($_SESSION['error_rezultatow']);
                     ?><a href ="historia_rezerwacji_pracownik.php">Wprowadź poprawnego użytkownika</a><?php
                     exit();

                   }
                   if(isset($_SESSION['brak_error_rezultatow']))
                   {
                     echo '<div class="error" style="color:green">'.$_SESSION['brak_error_rezultatow'].'</div>';
                     ?><?php
                   }
                    ?>
<input type="submit" name="potwierdz" value="Potwierdź klienta" />


<?php
  echo "<br /><br />Obecne rezerwacje:<br /><br />";


    $polaczenie = @new mysqli($host,$db_user,$db_password,$db_name); //@- wycisza błędy spowodowane przez dalsze instruckje //ustanowienie polaczenia przez mysqli przez przesłanie danych //rezerwuje nowa pamiec, a mysqli to metoda
    $sql="SELECT * FROM rezerwacje WHERE nr_id_klienta='$id'";
    $date = date('Y-m-d a', time());


    $rezultat=@$polaczenie->query($sql);
    $ile_rezultatow=$rezultat->num_rows; //zbieramy ilosc wystapien w tabeli
    if($ile_rezultatow==0)
    {
      echo '<div class="error" style="color:red">'."Jeśli nie widzisz swojej obecnej rezerwacji, nie masz uzupełnionych danych osobowych lub nie dokonałeś rezerwacji.<br />Kliknij poniżej, aby wrócić do panelu konta i uzupełnić swoje dane lub złożyć rezerwacje!</div>";

    }
    for($i=0; $i<$ile_rezultatow; $i++)
    {

      $wiersz[$i]=$rezultat->fetch_assoc();

      echo "---------------------------------------------<br />";
      echo "Indywidualny numer klienta: ".$wiersz[$i]['nr_id_klienta']."<br />";
      echo "Numer pokoju: ".$wiersz[$i]['nr_pokoju']."<br />";
      echo "Miejsce parkingowe: ".$wiersz[$i]['nr_parkingu']."<br />";
      echo "Data przyjazdu: ".$wiersz[$i]['data_przyjazdu']."<br />";
      echo "Numer odjazdu: ".$wiersz[$i]['data_odjazdu']."<br />";
      echo "Cena: ".$wiersz[$i]['cena']." zł"."<br />";
      echo "Czy opłacono?: ".$wiersz[$i]['opłacone']."<br />";
      echo "<br /><br />";
    }

   ?>

<br /><br />Rezerwacje z przeszłości:<br /><br />

<?php
    $polaczenie = @new mysqli($host,$db_user,$db_password,$db_name); //@- wycisza błędy spowodowane przez dalsze instruckje //ustanowienie polaczenia przez mysqli przez przesłanie danych //rezerwuje nowa pamiec, a mysqli to metoda
    $sql="SELECT * FROM historia_pobytow WHERE nr_id_klienta='$id'";

    $date = date('Y-m-d a', time());


    $rezultat=@$polaczenie->query($sql);
    $ile_rezultatow=$rezultat->num_rows; //zbieramy ilosc wystapien w tabeli
    if($ile_rezultatow==0)
    {
      echo '<div class="error" style="color:red">'."Jeśli nie widzisz swojej historii rezerwacji, oznacza to brak dokonanych rezerwacji.<br />Wróc do panelu konta i skontaktuj się z nami w razie dodatkowych pytań!</div>";

    }
    for($i=0; $i<$ile_rezultatow; $i++)
    {

      $wiersz[$i]=$rezultat->fetch_assoc();

      echo "Numer w historii pobytów: ".$wiersz[$i]['nr_id_pobytu']."<br />";
      echo "Numer rezerwacji: ".$wiersz[$i]['nr_rezerwacji']."<br />";
      echo "Indywidualny numer klienta: ".$wiersz[$i]['nr_id_klienta']."<br />";
      echo "Numer pokoju: ".$wiersz[$i]['nr_pokoju']."<br />";
      echo "Miejsce parkingowe: ".$wiersz[$i]['nr_parkingu']."<br />";
      echo "Data przyjazdu: ".$wiersz[$i]['data_zameldowania']."<br />";
      echo "Numer odjazdu: ".$wiersz[$i]['data_wymeldowania']."<br />";
      echo "Cena: ".$wiersz[$i]['cena']." zł"."<br />";
      echo "Czy opłacono?: ".$wiersz[$i]['opłacone']."<br /><br /><br /><br /><br />";


    }
    $polaczenie->close();
   ?>


</body>
</html>

Zaloguj<?php

  session_start(); // kazdy dokument korzydtajacy z sesji musi miec ta linijke, nie trzeba takiego polaczenia sesji konczyc, tak jka mysqli

  if((!isset($_POST['login'])) || (!isset($_POST['haslo'])))  //jesli nie jest ustawiona zmienna login i haslo w ogoolnej tablicy post to przechodzimy do formularza logowania
  {
    header('Location: index.php');
    exit();
  }

  require_once "connect.php"; //załączenie pliku ; require_once bo jesli nie uda sie dolaczyc pliku to nie zostanie "zaledwie" wygenerowane ostrzeżenie tylko zatrzymamy skrypt, bo to groźne, "once" bo php najpierw sprawdzi czy nie został wcześniej plik wczytany i jeśli dołączone wcześniej to nie dołącza ponownie

  $polaczenie = @new mysqli($host,$db_user,$db_password,$db_name); //@- wycisza błędy spowodowane przez dalsze instruckje //ustanowienie polaczenia przez mysqli przez przesłanie danych //rezerwuje nowa pamiec, a mysqli to metoda

  if ($polaczenie->connect_errno!=0)
  {
     echo "Error: ".$polaczenie->connect_errno ." Opis: ".$polaczenie->connect_error; // jesli blad to pokazujemy error number (errno) oraz opis (connect_error)

  }
  else
  {
    $login = $_POST['login'];
    $haslo = $_POST['haslo'];

    $login= htmlentities($login, ENT_QUOTES, "UTF-8"); //przepuszczenie podanego loginu i hasla przez htmlentites, co blokuje mysql injection, bo rozklada tresc zapytania na elementy tekstu a nie polecenie w jakimś języku

    #$sql = "SELECT * FROM konta WHERE login='$login' AND hasło='$haslo'"; i wtedy polaczenie->query($sql)
    // zamiast tego wyżej %s- oznaczenie miejsca gdzie bedzie zmienna, s-string, pierwszy %s to pierwszy argument podany po przecinku, drugi to drugi po przecinku,
    // tak też można, testowo
    if ($rezultat = @$polaczenie->query(sprintf("SELECT * FROM konta WHERE login='%s'",
    mysqli_real_escape_string($polaczenie,$login))))
    {
      $ilu_userow = $rezultat->num_rows; //sprawdzanie czy nasze query coś znalazło (użytkownika w bazie)
      if($ilu_userow>0)
      {
          $wiersz = $rezultat->fetch_assoc();

          if (password_verify($haslo, $wiersz['hasło']) OR $haslo=$wiersz['hasło']) // jest OR ale w tabeli jest hash, w przypadku tworzenia konta w mysql trzeba wybrać MD5 hashowanie! tak ok!
          {
            $_SESSION['zalogowany']=true;
            $_SESSION['id']=$wiersz['nr_id_konta'];
            $_SESSION['login']=$wiersz['login'];
            $_SESSION['email']=$wiersz['email'];
            $_SESSION['haslo']=$wiersz['hasło'];

            unset($_SESSION['blad']);
            // dodanie sprawdzania bitu pracownika
            if ($wiersz['pracownik']=='1')
            {
             $_SESSION['zalogowany_pracownik']=true;
              header('Location:konto_pracownik.php');
            }
            else{
              header('Location: konto.php');
            }

          }
          else
          {
            $_SESSION['blad']='<span style="color:red">Nieprawidlowy hash!</span>';
            header('Location:index.php');
          }
      } else
        {
          $_SESSION['blad']='<span style="color:red">Nieprawidlowy login lub hasło!</span>';
          header('Location:index.php');
        }

    }
    $polaczenie->close(); //zamkniecie polaczenie ( bo wyzej otwierane )
  }




 ?>

index
<?php
  session_start();
  ?>

  <link rel="stylesheet" href="style.css" type="text/css">
<?php
  if((isset($_SESSION['zalogowany'])) && ($_SESSION['zalogowany']==true)) // jesli jest uzytkownik zalogowany to przechodzimy do konta od razu
  {
    header('Location:konto.php');
    exit(); //nie wykonywać reszty kodu pod spodem, bo i tka przechodzimy do innego pliku .php
  }
 ?>

 <!DOCTYPE HTML>
 <html lang="pl">
 <head>
     <meta charset="utf-8" />
     <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
     <title> Logowanie </title>
</head>

<body>
  <br /><br />
  <br /><br />
  <form action="zaloguj.php" method="post">
<div id="container">
</form>
      Login: <br /> <input type="text" name="login" placeholder="login" onfocus="this.placeholder=''"  onblur="this.placeholder='login' "/> <br />
      Hasło: <br /> <input type="password" name="haslo" placeholder="hasło" onfocus="this.placeholder=''" onblur="this.placeholder='hasło' "/> <br /><br />
      <?php
        if(isset($_SESSION['blad']))  echo $_SESSION['blad']; // tylko jak ktoś próbował się zalogować to wyświetl błąd
        unset($_SESSION['blad']);

       ?>
    <input type ="submit" value="Zaloguj sie" />


  </form>
  <form action="rejestracja.php" method="get">
  <input type="submit" value="Załóż konto"></input>
</div>

  <h1>
  <p><br />Zdjęcie nie przedstawia prawdziwego hotelu , źródło: https://q-cf.bstatic.com/images/hotel/max1024x768/797/79726354.jpg</p>
</h1>


</body>
</html>

historia rezerwacji
<?php
  session_start();
  require_once "connect.php";
  ?>
  <link rel="stylesheet" href="style.css" type="text/css">
  <?php
  $id=$_SESSION['id'];
  ?>

 <!DOCTYPE HTML>
 <html lang="pl">
 <head>
     <meta charset="utf-8" />
     <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
     <title> Historia Rezerwacji </title>
</head>

<body>

<div id="container">
  <?php
      if(isset($_SESSION['zalogowany_pracownik']))
      {
        ?><form action="konto_pracownik.php" method="post">
          <input type="submit" value="Wróć "></input>
        <?php
      }
      else {
        ?><form action="index.php" method="post">
          <input type="submit" value="Wróć "></input>
        <?php
      }
       ?>
</form>
                              <?php

                                echo "<h3>Obecne rezerwacje:</h3>";

                                  $polaczenie = @new mysqli($host,$db_user,$db_password,$db_name); //@- wycisza błędy spowodowane przez dalsze instruckje //ustanowienie polaczenia przez mysqli przez przesłanie danych //rezerwuje nowa pamiec, a mysqli to metoda
                                  $sql="SELECT * FROM rezerwacje WHERE nr_id_klienta='$id'";
                                  $date = date('Y-m-d a', time());
                                  $rezultat=@$polaczenie->query($sql);
                                  $ile_rezultatow=$rezultat->num_rows; //zbieramy ilosc wystapien w tabeli
                                  if($ile_rezultatow==0)
                                  {
                                    echo '<div class="error" style="color:red">'."Jeśli nie widzisz swojej obecnej rezerwacji, nie masz uzupełnionych danych osobowych lub nie dokonałeś rezerwacji.<br />Kliknij poniżej, aby wrócić do panelu konta i uzupełnić swoje dane lub złożyć rezerwacje!"."</div>";

                                  }
                                  for($i=0; $i<$ile_rezultatow; $i++)
                                  {

                                    $wiersz[$i]=$rezultat->fetch_assoc();

                                    echo "--------------------------------------------<br />";
                                    echo "Numer pokoju: ".$wiersz[$i]['nr_pokoju']."<br />";
                                    echo "Miejsce parkingowe: ".$wiersz[$i]['nr_parkingu']."<br />";
                                    echo "Data przyjazdu: ".$wiersz[$i]['data_przyjazdu']."<br />";
                                    echo "Numer odjazdu: ".$wiersz[$i]['data_odjazdu']."<br />";
                                    echo "Cena: ".$wiersz[$i]['cena']." zł"."<br />";
                                    echo "Czy opłacono?: ".$wiersz[$i]['opłacone']."<br />";
                                    echo "<br /><br />";

                                  }


                                 echo "<h3>Przeszłe rezerwacje:</h3>";
                                 echo "--------------------------------------------<br />";



                                  $polaczenie = @new mysqli($host,$db_user,$db_password,$db_name); //@- wycisza błędy spowodowane przez dalsze instruckje //ustanowienie polaczenia przez mysqli przez przesłanie danych //rezerwuje nowa pamiec, a mysqli to metoda
                                  $sql="SELECT * FROM historia_pobytow WHERE nr_id_klienta='$id'";
                                  $date = date('Y-m-d a', time());


                                  $rezultat=@$polaczenie->query($sql);
                                  $ile_rezultatow=$rezultat->num_rows; //zbieramy ilosc wystapien w tabeli
                                  if($ile_rezultatow==0)
                                  {
                                    echo '<div class="error" style="color:red">'."Jeśli nie widzisz swojej historii rezerwacji, oznacza to brak dokonanych rezerwacji w naszej bazie.<br />Wróc do panelu konta i skontaktuj się z nami w razie dodatkowych pytań!"."</div>";

                                  }
                                  for($i=0; $i<$ile_rezultatow; $i++)
                                  {

                                    $wiersz[$i]=$rezultat->fetch_assoc();

                                    echo "Numer w historii pobytów: ".$wiersz[$i]['nr_id_pobytu']."<br />";
                                    echo "Numer rezerwacji: ".$wiersz[$i]['nr_rezerwacji']."<br />";
                                    echo "Indywidualny numer klienta: ".$wiersz[$i]['nr_id_klienta']."<br />";
                                    echo "Numer pokoju: ".$wiersz[$i]['nr_pokoju']."<br />";
                                    echo "Miejsce parkingowe: ".$wiersz[$i]['nr_parkingu']."<br />";
                                    echo "Data przyjazdu: ".$wiersz[$i]['data_zameldowania']."<br />";
                                    echo "Numer odjazdu: ".$wiersz[$i]['data_wymeldowania']."<br />";
                                    echo "Cena: ".$wiersz[$i]['cena']." zł"."<br />";
                                    echo "Czy opłacono?: ".$wiersz[$i]['opłacone']."<br /><br /><br /><br /><br />";
                                  }
                                  $polaczenie->close();
                                 ?>

</div>

</body>
</html>
style.css
body{
  background-image: url("index.png"), url("31.png"),  url("21.png");
  font-size:20px;
}

h1
{
  font-size:20px;
  opacity: 0.9;
  padding:50px;
  color:#eef78e;

}
h2
{
  font-size:20px;
  padding:50px;
  opacity:0.5px;
}
h3
{
  font-family: Courier;
  margin-left: -25px;
  margin-right:-100px;
    font-size:20px;
    padding: 0px;

    opacity: 0.9;

}


#container
{
  background-color:white;
  width:300px;
  padding:50px;
  margin-left: auto;
  margin-right: auto;
  margin-top:100px;
  -webkit-box-shadow: 3px 3px 30px 5px rgba(204,204,204,0.9);
  -moz-box-shadow: 3px 3px 30px 5px rgba(204,204,204,0.9);
  box-shadow: 3px 3px 30px 5px rgba(204,204,204,0.9);
}
input[type=text],
input[type=password]
{
    width: 300px;
    background-color: #efefef;
    color: #666;
    border: 2px solid #ddd;
    border-radius: 5px;
    font-size: 20px;
    padding:10px;
    outline:none;
}
input[type=text]:focus,
input[type=password]:focus
{
  -webkit-box-shadow: 0px 0px 10px 2px rgba(204,204,204,0.9);
  -moz-box-shadow: 0px 0px 10px 2px rgba(204,204,204,0.9);
  box-shadow: 0px 0px 10px 2px rgba(204,204,204,0.9);
  border: 2px solid #a5cda5;
  background-color: #e9f3e9;
  color: #428c42;
}
input[type=submit]
{
  width:300px;
  background-color: #36b03c;
  font-size:20px;
  color:white;
  padding: 10px 10px;
  margin-top: 15px;
  border-radius: 5px;
  cursor: pointer;
  letter-spacing: 4px;
  outline: none;
}
input[type=submit]:focus
{
  -webkit-box-shadow: 0px 0px 15px 5px rgba(204,204,204,0.9);
  -moz-box-shadow: 0px 0px 15px 5px rgba(204,204,204,0.9);
  box-shadow: 0px 0px 15px 5px rgba(204,204,204,0.9);
}

input[type=submit]:hover
{
  background-color: #37b93d;
}

input::-webkit-input-placeholder
{
  color: #999;
}
input:focus::-webkit-input-placeholder
{
  color: #428c42;
}
input:-moz-placeholder
{
  color: #999;
}
input:focus:-moz-placeholder
{
  color: #428c42;
}
input::-moz-placeholder
{
  color: #999;
}
input:focus::-moz-placeholder
{
  color:#428c42;
}
input:-ms-input-placeholder
{
  color:#999;
}
input:focus:-ms-input-placeholder
{
  color:#428c42;
}

konto_pracownik
<?php
  session_start();
  require_once "connect.php"; // bardzo ważne, żeby zrobic potem połączenie i porównywać rzeczy
  ?>
  <link rel="stylesheet" href="style.css" type="text/css">
<?php

  $id=$_SESSION['id'];
  $date = date('Y-m-d a', time()); // przypisanie daty do zmiennej w odpowiednim formacie


  if(!isset($_SESSION['zalogowany'])) //nie jestesmy zalogowani
  {
    header('Location:index.php');
    exit();
  }

 ?>

 <!DOCTYPE HTML>
 <html lang="pl">
 <head>
     <meta charset="utf-8" />
     <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
     <title> Konto Pracownik </title>
</head>

<body>
  <div id="container">
<?php

  //sprawdzanie przestarzalych rezerwacji: //sprawdzic ile rekordow, zrobic petle for, dla kazdego pobierac do wiersza i przesylac przez insert into
  $polaczenie = @new mysqli($host,$db_user,$db_password,$db_name); //@- wycisza błędy spowodowane przez dalsze instruckje //ustanowienie polaczenia przez mysqli przez przesłanie danych //rezerwuje nowa pamiec, a mysqli to metoda


        $polaczenie = @new mysqli($host,$db_user,$db_password,$db_name); //@- wycisza błędy spowodowane przez dalsze instruckje //ustanowienie polaczenia przez mysqli przez przesłanie danych //rezerwuje nowa pamiec, a mysqli to metoda
        $date = date('Y-m-d', time());
            $sql99="SELECT * FROM rezerwacje WHERE rezerwacje.data_odjazdu<'$date'";
            $rezultat=@$polaczenie->query($sql99);
            $ile_rezultatow=$rezultat->num_rows; //zbieramy ilosc wystapien w tabeli
            for($i=0;$i<$ile_rezultatow;$i++)
            {
              $wiersz[$i]=$rezultat->fetch_assoc();
              if($date>$wiersz[$i]['data_odjazdu'])
              {
                    $nr_rezerwacji=$wiersz[$i]['nr_rezerwacji'];
                    $nr_id_klienta=$wiersz[$i]['nr_id_klienta'];
                    $nr_pokoju=$wiersz[$i]['nr_pokoju'];
                    $nr_parkingu=$wiersz[$i]['nr_parkingu'];
                    $data_przyjazdu=$wiersz[$i]['data_przyjazdu'];
                    $data_odjazdu=$wiersz[$i]['data_odjazdu'];
                    $cena=$wiersz[$i]['cena'];
                    $oplacone=$wiersz[$i]['opłacone'];
                    $sql98="INSERT INTO `historia_pobytow`(`nr_id_pobytu`, `nr_rezerwacji`, `nr_id_klienta`, `nr_pokoju`, `nr_parkingu`, `data_zameldowania`, `data_wymeldowania`, `cena`, `opłacone`, `timestamp`) VALUES (NULL,'$nr_rezerwacji','$nr_id_klienta','$nr_pokoju','$nr_parkingu','$data_przyjazdu','$data_odjazdu','$cena','$oplacone',NOW())";
                    $rezultat98=@$polaczenie->query($sql98);
                    $sql97="DELETE FROM rezerwacje WHERE '$date'>'$data_odjazdu' ORDER BY rezerwacje.data_odjazdu LIMIT 1 "; //DELETE FROM rezerwacje
                    $rezultat97=@$polaczenie->query($sql97);
                    $polaczenie->query("UPDATE pokoje SET status='wolny' WHERE nr_pokoju='$nr_pokoju' ");
                    $polaczenie->query("UPDATE parkingi SET status='wolne' WHERE miejsce='$nr_parkingu' && status!='brak' ");
                  }
                }
                ?>
                <form action="logout.php" method="get">
                   <input type="submit" value="Wyloguj się"></input></form>
                   <?php    echo "<h3>Witaj pracowniku ".$_SESSION['login']."</h3>";?>

                 <form action="dane.php" method="get">
                 <input type="submit" value="Zobacz swoje dane"></input></form>
                 <form action="zmien.php" method="get">
                 <input type="submit" value="Zmień swoje dane"></input></form>
                 <form action="rejestracja.php" method="get">
                 <input type="submit" value="Dodaj użytkownika"></input></form>
                 <form action="historia_rezerwacji_pracownik.php" method="get">
                 <input type="submit" value="Przeglądaj rezerwacje klientów"></input></form>
                 <form action="zarezerwuj_pracownik.php" method="get">
                 <input type="submit" value="Zrób rezerwację"></input></form>

                 <?php
   $polaczenie->close();

 ?>
</div>
<h1>
<p><br />Zdjęcie nie przedstawia prawdziwego hotelu , źródło: https://q-cf.bstatic.com/images/hotel/max1024x768/216/216004253.jpg</p>
</h1>
</body>
</html>
zarezerwuj<?php

session_start();
error_reporting(E_ERROR);
require_once "connect.php";
?>
<link rel="stylesheet" href="style.css" type="text/css">
<?php

$nr_pokoju=$_POST['nr_pokoju'];

$miejsce_parkingowe=$_POST['miejsce_parkingowe'];
$miejsce_parkingowe= htmlentities($miejsce_parkingowe, ENT_QUOTES, "UTF-8");


$data_przyjazdu=$_POST['data_przyjazdu'];
$data_odjazdu=$_POST['data_odjazdu'];

$poprawnosc=0;
$p=0;
$b=0;
$id=$_SESSION['id'];
$login=$_SESSION['login'];
$date = date('Y-m-d', time());
$cos=0;


$tabela[22]=[];
$tabela2[19]=[];
 ?>

<!DOCTYPE HTML>
<html lang="pl">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <title> Zarezerwuj pokój </title>
</head>

<body>
  <div id="container">
    <?php
        if(isset($_SESSION['zalogowany_pracownik']))
        {
          ?><form action="konto_pracownik.php" method="post">
            <input type="submit" value="Wróć "></input>
          <?php
        }
        else {
          ?><form action="index.php" method="post">
            <input type="submit" value="Wróć "></input>
          <?php
        }
         ?>
 <br /><br />

<?php
// sprawdzanie czy taki użytkownik ma uzupełnione dane ( wymagane aby złozyć rezerwacje !)
$polaczenie = @new mysqli($host,$db_user,$db_password,$db_name); //@- wycisza błędy spowodowane przez dalsze instruckje //ustanowienie polaczenia przez mysqli przez przesłanie danych //rezerwuje nowa pamiec, a mysqli to metoda
$sql="SELECT * FROM klienci WHERE nr_id_klienta='$id' ";
$rezultat=@$polaczenie->query($sql);
$wiersz = $rezultat->fetch_assoc();
$ile_rezultatow=$rezultat->num_rows; //zbieramy ilosc wystapien w tabeli
if($ile_rezultatow=='0')
{
  $_SESSION['error_klienci']="Brak uzupełnionych danych !";
  $cos=1;
}
if(isset($_SESSION['error_klienci']))
{
  echo '<div class="error" style="color:red">'.$_SESSION['error_klienci'].'</div>';
  unset($_SESSION['error_klienci']); // zeby przy nastepnym wyslaniu formularza mozna bylo poprawic bledy

}

 ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
 ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
 ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
 //                                                    TA CZĘŚĆ ODPOWIADA ZA SPRAWDZANIE DATY
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
?>
</form>
<form method="post">

      <?php
      session_start();
      if (($data_odjazdu < $data_przyjazdu && $data_przyjazdu>=$date && $data_odjazdu>=$date ) || ( $data_odjazdu > $data_przyjazdu && $data_przyjazdu<$date && $data_odjazdu<=$date ) || ( $data_odjazdu < $data_przyjazdu && $data_przyjazdu<=$date && $data_odjazdu<=$date ) || ( $data_odjazdu > $data_przyjazdu && $data_przyjazdu<$date && $data_odjazdu>=$date ) || ( $data_odjazdu < $data_przyjazdu && $data_przyjazdu>=$date && $data_odjazdu<=$date ))
        {
          $_SESSION['e_daty']="Data przyjazdu nie może być później niż odjazdu lub rezerwacja nie może odbyć się w minionym terminie!";
        }
       ?>

                       Data przyjazdu: <br /><input type="date" name="data_przyjazdu" value=<?php echo $data_przyjazdu; ?>  max="2020-12-30" /><br />
                       Data odjazdu: <br /><input type="date" name="data_odjazdu" value=<?php echo $data_odjazdu; ?>  max="2020-12-30" /><br /><br />
                                 <?php
                                 session_start();
                                 if(isset($_SESSION['e_daty']) )
                                 {
                                   #$wszystko_OK=false;
                                   echo '<div class="error" style="color:red">'.$_SESSION['e_daty'].'</div>';
                                   unset($_SESSION['e_daty']); // zeby przy nastepnym wyslaniu formularza mozna bylo poprawic bledy
                                   ?><a href ="zarezerwuj.php">Wybierz poprawną datę</a><?php
                                   exit();
                                   ?>
                                   <?php

                                 }?>
                                 <input type="submit" value="Przejdź do wyboru pokoju i parkingu" name="submit"/>


<?php

 ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
 ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
 ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
 //                                                    TA CZĘŚĆ ODPOWIADA ZA WYSWIETLANIE WOLNYCH POKOJÓW I PARKINGÓW
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
          session_start();
          if(isset($_POST['submit']) ) //jeśli nacisnięto submit
          {
            echo "---------------------------------------------<br />";
            echo "<h4>"." Wolne pokoje:<br /></h4>";
            echo "---------------------------------------------<br />";

          $polaczenie = @new mysqli($host,$db_user,$db_password,$db_name); //@- wycisza błędy spowodowane przez dalsze instruckje //ustanowienie polaczenia przez mysqli przez przesłanie danych //rezerwuje nowa pamiec, a mysqli to metoda

          $date = date('Y-m-d a', time());

          $sql="SELECT rezerwacje.nr_id_klienta,pokoje.nr_pokoju,pokoje.rodzaj_pokoju,pokoje.cena FROM pokoje,rezerwacje
          WHERE  (rezerwacje.nr_pokoju = pokoje.nr_pokoju && rezerwacje.status!='aktywny' && (('$data_przyjazdu'>rezerwacje.data_odjazdu && '$data_odjazdu'>rezerwacje.data_odjazdu) || ('$data_przyjazdu'<rezerwacje.data_przyjazdu && '$data_odjazdu'<rezerwacje.data_przyjazdu) )) ";
          $rezultat=@$polaczenie->query($sql);
          $ile_rezultatow=$rezultat->num_rows; //zbieramy ilosc wystapien w tabeli
          for($i=0; $i<$ile_rezultatow; $i++)
          {
            $wiersz[$i]=$rezultat->fetch_assoc();
            // sprawdzanie czy numer pokoju się powtarza, bo jeśli się powtarzał to potrafiło wyświetlić numer pokoju dostępny, bo np była późniejsza rezerwacja i jak sprawdzaliśmy wcześniejszą datą, to ona przechodziła nasz warunel - daty wcześniejsze , więc sprawdzanie ile razy wystąpiło i wyświetlanie tylko wtedy gdy ten pokój spełnia warunek ZAWSZE, TYLE RAZY ILE KWERENDA OBLICZY
            $test=$wiersz[$i]['nr_pokoju'];
            $zliczenia=0;
            $wyswietlone=0;
            $sql_liczba="SELECT * FROM rezerwacje WHERE $test=rezerwacje.nr_pokoju";
            $rezultat_liczby=@$polaczenie->query($sql_liczba);
            $ile_rezultatow_liczby=$rezultat_liczby->num_rows;


            for($x=0;$x<$ile_rezultatow;$x++)
            {
              if($wiersz[$x]['nr_pokoju']==$test)
                {
                    $zliczenia++;
                }
              if($ile_rezultatow_liczby==$zliczenia)
              {
                $zliczenia=0;
                if($nr_pokoju=$test)
                {
                  echo '<h3>'."Pokój numer: ".$wiersz[$i]['nr_pokoju']."<br/> ".$wiersz[$i]['rodzaj_pokoju'].", cena:  ".$wiersz[$i]['cena']."zł</h2>";
                }

              }
            }
          }
           ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
           ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
           ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
           //                                                    Ta część odpowiada za wyświetlanie wolnych parkingów
          ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
          ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
          echo "---------------------------------------------<br />";
          echo "<h4>"."Wolne miejsca parkingowe:</h4>";
          echo "---------------------------------------------<br />";

          $sql="SELECT rezerwacje.nr_parkingu,parkingi.miejsce, parkingi.id_parkingu,rezerwacje.data_przyjazdu,rezerwacje.data_odjazdu,rezerwacje.status,parkingi.cena FROM parkingi,rezerwacje
          WHERE  (rezerwacje.nr_parkingu = parkingi.id_parkingu && rezerwacje.status!='aktywny' && parkingi.status!='brak' && (('$data_przyjazdu'>rezerwacje.data_odjazdu && '$data_odjazdu'>rezerwacje.data_odjazdu) || ('$data_przyjazdu'<rezerwacje.data_przyjazdu && '$data_odjazdu'<rezerwacje.data_przyjazdu) )  )";
          $rezultat=@$polaczenie->query($sql);
          $ile_rezultatow=$rezultat->num_rows; //zbieramy ilosc wystapien w tabeli
          for($i=0; $i<$ile_rezultatow; $i++)
          {
            $wiersz[$i]=$rezultat->fetch_assoc();
            // sprawdzanie czy numer pokoju się powtarza, bo jeśli się powtarzał to potrafiło wyświetlić numer pokoju dostępny, bo np była późniejsza rezerwacja i jak sprawdzaliśmy wcześniejszą datą, to ona przechodziła nasz warunel - daty wcześniejsze , więc sprawdzanie ile razy wystąpiło i wyświetlanie tylko wtedy gdy ten pokój spełnia warunek ZAWSZE, TYLE RAZY ILE KWERENDA OBLICZY
            $test=$wiersz[$i]['nr_parkingu'];
            $zliczenia=0;
            $wyswietlone=0;
            $sql_liczba="SELECT * FROM rezerwacje WHERE $test=rezerwacje.nr_parkingu";
            $rezultat_liczby=@$polaczenie->query($sql_liczba);
            $ile_rezultatow_liczby=$rezultat_liczby->num_rows;

            for($x=0;$x<$ile_rezultatow;$x++)
            {
              if($wiersz[$x]['nr_parkingu']==$test)
                {
                    $zliczenia++;
                }
              if($ile_rezultatow_liczby==$zliczenia)
              {
                $zliczenia=0;
                  echo '<h3>'."Parking numer: ".$wiersz[$i]['miejsce']." cena: ".$wiersz[$i]['cena']."zł <br /></h3>";
              }
            }

          }
          echo "---------------------------------------------<br /><br/>";


          }
           ?>

                              Wybierz numer pokoju: <br /><input type="text" name="nr_pokoju"/><br />
                             Wybierz numer miejsca parkingowego: <br /><input type="text" name="miejsce_parkingowe"/><br />

                     <input type="submit" value="Rezerwuj" name="submit2"/>
                     </form>
<br />



    <?php
    session_start();



///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//TA CZĘŚĆ ODPOWIADA ZA AKCJE PO NACIŚNIĘCIU REZERWUJ -- czyli sprawdzenie  czy pokój znajduje siee ( i zliczanie ile razy, aby w przypadku wielokrotnego wystapienia wziac pod uwage ze moze byc pokoj niedostepny w terminie oraz ten sam pokoj dostepnym w innym ) w tablicy która zbierała wolne pokoje i to samo z parkingami
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                        if(isset($_POST['submit2']))
                        {

                          $polaczenie = @new mysqli($host,$db_user,$db_password,$db_name); //@- wycisza błędy spowodowane przez dalsze instruckje //ustanowienie polaczenia przez mysqli przez przesłanie danych //rezerwuje nowa pamiec, a mysqli to metoda


                          $sql="SELECT rezerwacje.nr_id_klienta,pokoje.nr_pokoju,pokoje.rodzaj_pokoju,pokoje.cena FROM pokoje,rezerwacje
                          WHERE  (rezerwacje.nr_pokoju = pokoje.nr_pokoju && rezerwacje.status!='aktywny' && (('$data_przyjazdu'>rezerwacje.data_odjazdu && '$data_odjazdu'>rezerwacje.data_odjazdu) || ('$data_przyjazdu'<rezerwacje.data_przyjazdu && '$data_odjazdu'<rezerwacje.data_przyjazdu) )) ";
                          $rezultat=@$polaczenie->query($sql);
                          $ile_rezultatow=$rezultat->num_rows; //zbieramy ilosc wystapien w tabeli
                          for($i=0; $i<$ile_rezultatow; $i++)
                          {
                            $wiersz[$i]=$rezultat->fetch_assoc();
                            $test=$wiersz[$i]['nr_pokoju'];
                            $zliczenia=0;
                            $wyswietlone=0;
                            $sql_liczba="SELECT * FROM rezerwacje WHERE $test=rezerwacje.nr_pokoju";
                            $rezultat_liczby=@$polaczenie->query($sql_liczba);
                            $ile_rezultatow_liczby=$rezultat_liczby->num_rows;

                            for($x=0;$x<$ile_rezultatow;$x++)
                            {
                              if($wiersz[$x]['nr_pokoju']==$test)
                                {
                                    $zliczenia++;
                                }
                              if($ile_rezultatow_liczby==$zliczenia)
                              {
                                $zliczenia=0;
                                if($nr_pokoju=$test)
                                {
                                  $tabela[$b]=$wiersz[$i]['nr_pokoju'];
                                  $b++;
                                }

                              }
                            }
                          }
                          $nr_pokoju=$_POST['nr_pokoju'];

                          $sql="SELECT rezerwacje.nr_parkingu,parkingi.miejsce, parkingi.id_parkingu,rezerwacje.data_przyjazdu,rezerwacje.data_odjazdu,rezerwacje.status,parkingi.cena FROM parkingi,rezerwacje
                          WHERE  (rezerwacje.nr_parkingu = parkingi.id_parkingu && rezerwacje.status!='aktywny' && (('$data_przyjazdu'>rezerwacje.data_odjazdu && '$data_odjazdu'>rezerwacje.data_odjazdu) || ('$data_przyjazdu'<rezerwacje.data_przyjazdu && '$data_odjazdu'<rezerwacje.data_przyjazdu) )  )";
                          $rezultat=@$polaczenie->query($sql);
                          $ile_rezultatow=$rezultat->num_rows; //zbieramy ilosc wystapien w tabeli
                          for($i=0; $i<$ile_rezultatow; $i++)
                          {
                            $wiersz[$i]=$rezultat->fetch_assoc();
                            // sprawdzanie czy numer pokoju się powtarza, bo jeśli się powtarzał to potrafiło wyświetlić numer pokoju dostępny, bo np była późniejsza rezerwacja i jak sprawdzaliśmy wcześniejszą datą, to ona przechodziła nasz warunel - daty wcześniejsze , więc sprawdzanie ile razy wystąpiło i wyświetlanie tylko wtedy gdy ten pokój spełnia warunek ZAWSZE, TYLE RAZY ILE KWERENDA OBLICZY
                            $test=$wiersz[$i]['nr_parkingu'];
                            $zliczenia=0;
                            $wyswietlone=0;
                            $sql_liczba="SELECT * FROM rezerwacje WHERE $test=rezerwacje.nr_parkingu";
                            $rezultat_liczby=@$polaczenie->query($sql_liczba);
                            $ile_rezultatow_liczby=$rezultat_liczby->num_rows;

                            for($x=0;$x<$ile_rezultatow;$x++)
                            {
                              if($wiersz[$x]['nr_parkingu']==$test)
                                {
                                    $zliczenia++;
                                }
                              if($ile_rezultatow_liczby==$zliczenia)
                              {
                                $zliczenia=0;
                                  $tabela2[$p]=$wiersz[$i]['miejsce'];
                                  $p++;
                              }
                            }

                          }
                         // sprawdzanie poprawnosci pokojow czy
                         $rozmiar=count($tabela)+1; // pobieranie wartości
                         $nr_pokoju2=$nr_pokoju;
                         for($i=0;$i<$rozmiar+1;$i++)
                         {

                           if ($nr_pokoju2==$tabela[$i])
                           {
                             $poprawnosc++;
                           }

                         }
                         if($poprawnosc>'1')
                         {
                              $poprawnosc=1;
                             $_SESSION['e_nr_pokoju']="NIEPOPRAWNY NR POKOJU";

                         }
                         if(isset($_SESSION['e_nr_pokoju']))
                         {
                           unset($_SESSION['e_nr_pokoju']); // zeby przy nastepnym wyslaniu formularza mozna bylo poprawic bledy
                         }

                         $rozmiar2=count($tabela2)+1;

                         $nr_parkingu2=$miejsce_parkingowe;

                         for($i=0;$i<$rozmiar2+1;$i++)
                         {

                           if($nr_parkingu2==$tabela2[$i])
                           {

                             $poprawnosc++;
                           }

                         }
                         if($poprawnosc>'2')
                         {
                           $poprawnosc=2;
                             $_SESSION['e_nr_pokoju']="NIEPOPRAWNY NR PARKINGU";

                         }

                      //sumujemy ceny za pokój i parking
                      $sql="SELECT * FROM pokoje WHERE nr_pokoju='$nr_pokoju'";
                      $rezultat=@$polaczenie->query($sql);
                      $wiersz=$rezultat->fetch_assoc();
                      $cena1=$wiersz['cena'];
                        $sql="SELECT * FROM parkingi WHERE miejsce='$miejsce_parkingowe'";
                        $rezultat=@$polaczenie->query($sql);
                        $wiersz=$rezultat->fetch_assoc();
                        $cena2=$wiersz['cena'];
                          $cena3=$cena1+$cena2;



    if($poprawnosc=='2' && !isset($_SESSION['zalogowany_pracownik']) && $cos!=1)
    {
          unset($_SESSION['brak_error_rezultatow']);
            if($miejsce_parkingowe==0)
            {
              $polaczenie->query("INSERT INTO `rezerwacje`( `nr_id_klienta`, `nr_pokoju`, `nr_parkingu`, `data_przyjazdu`, `data_odjazdu`, `cena`, `opłacone`, `status`) VALUES ( '$id', '$nr_pokoju', '0', '$data_przyjazdu', '$data_odjazdu', '$cena3', 'tak','tak')");
            }                                                                                              # tutaj
            $polaczenie->query("INSERT INTO `rezerwacje`( `nr_id_klienta`, `nr_pokoju`, `nr_parkingu`, `data_przyjazdu`, `data_odjazdu`, `cena`, `opłacone`, `status`) VALUES ( '$id', '$nr_pokoju','$miejsce_parkingowe', '$data_przyjazdu', '$data_odjazdu', '$cena3', 'tak','tak')");
            unset($_SESSION['error']); // wyciszam error, bo jak sie wypełni date i potem submituje to po wypełnieniu pokoju i parkingu nie pamięta że data jest wybrana
            $_SESSION['success']="Rezerwacja udana";
      }
      else {
          if(isset($_POST['submit2']))
        {
        $_SESSION['not_ok']=" Podałeś zły numer pokoju i parkingu lub jesteś pracownikiem.<br /> Jeśli jesteś klientem, uzupełnij swoje dane!";
        echo '<div class="error" style="color:RED">'.$_SESSION['not_ok'].'</div>';
        unset($_SESSION['not_ok']); // zeby przy nastepnym wyslaniu formularza mozna bylo poprawic ble
        exit();
        }
        }
        $polaczenie->close();
        }

?>


<?php

if(isset($_SESSION['success']))
{
  #$wszystko_OK=false;
  echo '<div class="error" style="color:green">'.$_SESSION['success'].'</div>';
  unset($_SESSION['success']); // zeby przy nastepnym wyslaniu formularza mozna bylo poprawic bledy
}


  unset($_SESSION['blad']);
  unset($_SESSION['e_status_pokoj']);
  unset($_SESSION['error']);
  unset($_SESSION['error_klienci']); // zeby przy nastepnym wyslaniu formularza mozna bylo poprawic bledy


 ?>
</div>
</body>
</html>
