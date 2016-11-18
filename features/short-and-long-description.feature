Feature: projection of short and long description in CDBXML.
  @issue-III-1126
  Scenario: strip description to a short description of max. 400 characters for CDBXML whitout HTML-tags.
    Given an event
    When I changed the description to
    """
    <p>Ook in 2015 houden we weer een feestje in de buurt van de Pieter Coutereelstraat. Deze keer op zaterdag
    15 augustus. Op het programma:<br>
    LIVE MUZIEK (vanaf 16:00 uur)<br>
    -----------------------------------------------------<br>
    * Les Talons Gitans<br>
    * Bourdon Willie<br>
    * Carl Durant &amp; The Lost Kings<br>
    * Cherchez La Femme<br>
    * One Man Brawl<br>
    * De Zingende Apen<br><br>
    DOORLOPEND (vanaf 12:00 uur)<br>
    -----------------------------------------------------<br>
    * springkasteel, rad van fortuin kinderanimatie, muziekworkshop,...<br>
    * lekkere hapjes en frisse drankjes aan democratische prijzen<br>
    * pop-up verrassingsbar met diverse aanbiedingen<br>
    * Repair Café met infomarkt voor een duurzame buurt<br></p>
    """
    Then the short description in CDBXML should be
    """
    Ook in 2015 houden we weer een feestje in de buurt van de Pieter Coutereelstraat. Deze keer op zaterdag 15 augustus. Op het programma: LIVE MUZIEK (vanaf 16:00 uur) ----------------------------------------------------- * Les Talons Gitans * Bourdon Willie * Carl Durant The Lost Kings * Cherchez La Femme * One Man Brawl * De Zingende Apen DOORLOPEND (vanaf 12:00 uur) -------------------------------
    """

  Scenario: project description as long description in CDBXML.
    Given an event
    When I changed the description to
    """
    <p>Ook in 2015 houden we weer een feestje in de buurt van de Pieter Coutereelstraat. Deze keer op zaterdag
    15 augustus. Op het programma:<br>
    LIVE MUZIEK (vanaf 16:00 uur)<br>
    -----------------------------------------------------<br>
    * Les Talons Gitans<br>
    * Bourdon Willie<br>
    * Carl Durant &amp; The Lost Kings<br>
    * Cherchez La Femme<br>
    * One Man Brawl<br>
    * De Zingende Apen<br><br>
    DOORLOPEND (vanaf 12:00 uur)<br>
    -----------------------------------------------------<br>
    * springkasteel, rad van fortuin kinderanimatie, muziekworkshop,...<br>
    * lekkere hapjes en frisse drankjes aan democratische prijzen<br>
    * pop-up verrassingsbar met diverse aanbiedingen<br>
    * Repair Café met infomarkt voor een duurzame buurt<br></p>
    """
    Then the long description in CDBXML should be
    """
    <p>Ook in 2015 houden we weer een feestje in de buurt van de Pieter Coutereelstraat. Deze keer op zaterdag
    15 augustus. Op het programma:<br>
    LIVE MUZIEK (vanaf 16:00 uur)<br>
    -----------------------------------------------------<br>
    * Les Talons Gitans<br>
    * Bourdon Willie<br>
    * Carl Durant &amp; The Lost Kings<br>
    * Cherchez La Femme<br>
    * One Man Brawl<br>
    * De Zingende Apen<br><br>
    DOORLOPEND (vanaf 12:00 uur)<br>
    -----------------------------------------------------<br>
    * springkasteel, rad van fortuin kinderanimatie, muziekworkshop,...<br>
    * lekkere hapjes en frisse drankjes aan democratische prijzen<br>
    * pop-up verrassingsbar met diverse aanbiedingen<br>
    * Repair Café met infomarkt voor een duurzame buurt<br></p>
    """