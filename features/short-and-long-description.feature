Feature: projection of short and long description in CDBXML.
  @issue-III-1126
  Scenario: strip description to a short description of max. 400 characters for CDBXML whitout HTML-tags.
    Given an event
    When I changed the description to
    """
    Ook in 2015 houden we weer een feestje in de buurt van de Pieter Coutereelstraat. Deze keer op zaterdag 15 augustus. Op het programma:\n
    LIVE MUZIEK (vanaf 16:00 uur)\n
    -----------------------------------------------------\n
    * Les Talons Gitans\n
    * Bourdon Willie\n
    * Carl Durant &amp; The Lost Kings\n
    * Cherchez La Femme\n
    * One Man Brawl\n
    * De Zingende Apen\n\n
    DOORLOPEND (vanaf 12:00 uur)\n
    -----------------------------------------------------\n
    * springkasteel, rad van fortuin kinderanimatie, muziekworkshop,...\n
    * lekkere hapjes en frisse drankjes aan democratische prijzen\n
    * pop-up verrassingsbar met diverse aanbiedingen\n
    * Repair Café met infomarkt voor een duurzame buurt\n
    """
    # linebreaks in UDB3 are saved as \n

    Then the short description in CDBXML should be
    """
    Ook in 2015 houden we weer een feestje in de buurt van de Pieter Coutereelstraat. Deze keer op zaterdag 15 augustus. Op het programma: LIVE MUZIEK (vanaf 16:00 uur) ----------------------------------------------------- * Les Talons Gitans * Bourdon Willie * Carl Durant & The Lost Kings * Cherchez La Femme * One Man Brawl * De Zingende Apen DOORLOPEND (vanaf 12:00 uur) --------------------------- ...
    """
    # korte beschrijving afkappen zodat deze max 396 karakters bevat: + ' ...' (= max 400)
    # op laatste leesteken[.|?|!] voor de limiet of laatste spatie voor de limiet?

  Scenario: project description as long description in CDBXML.
    Given an event
    And the sameAs value of this event equals:
    """
    http://www.uitinvlaanderen.be/agenda/e/een-piano-in-de-tu-n-joodse-rituelen-en-gebruiken/3aee552e-2071-46a1-beff-d73b31718ea6
    """
    When I changed the description to
    """
    Ook in 2015 houden we weer een feestje in de buurt van de Pieter Coutereelstraat. Deze keer op zaterdag
    15 augustus. Op het programma:\n
    LIVE MUZIEK (vanaf 16:00 uur)\n
    -----------------------------------------------------\n
    * Les Talons Gitans\n
    * Bourdon Willie\n
    * Carl Durant &amp; The Lost Kings\n
    * Cherchez La Femme\n
    * One Man Brawl\n
    * De Zingende Apen\n\n
    DOORLOPEND (vanaf 12:00 uur)\n
    -----------------------------------------------------\n
    * springkasteel, rad van fortuin kinderanimatie, muziekworkshop,...\n
    * lekkere hapjes en frisse drankjes aan democratische prijzen\n
    * pop-up verrassingsbar met diverse aanbiedingen\n
    * Repair Café met infomarkt voor een duurzame buurt\n
    """
    Then the long description in CDBXML should be
    """
    Ook in 2015 houden we weer een feestje in de buurt van de Pieter Coutereelstraat. Deze keer op zaterdag
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
    * Repair Café met infomarkt voor een duurzame buurt<br>
    <p class="uiv-source">Bron: <a href="http://www.uitinvlaanderen.be/agenda/e/een-piano-in-de-tu-n-joodse-rituelen-en-gebruiken/3aee552e-2071-46a1-beff-d73b31718ea6">UiTinVlaanderen.be</a></p>
    """
    # lange bescrhijving is volledige beschrijving van UDB3

  @issue-III-1126
  Scenario: split description to a short description of max. 400 characters without html for CDBXML and a long description.
    Given an event in UDB3
    And the sameAs value of this event equals:
    """
    http://www.uitinvlaanderen.be/agenda/e/een-piano-in-de-tu-n-joodse-rituelen-en-gebruiken/3aee552e-2071-46a1-beff-d73b31718ea6
    """
    When the description of this event is updated to:
	"""
    Korte beschrijving - Lorem ipsum dolor sit amet, consectetur
    adipiscing elit: Donec non velit eu eros eleifend mattis. Mauris
    tristique scelerisque consectetur. Morbi a congue purus, quis
    tempor arcu. Nam bibendum risus vel nulla feugiat finibus. Aenean
    vestibulum nisi vel nisl elementum, quis faucibus ex dictum
    nullam.\n\n
    Lange Beschrijving - Donec porta molestie arcu, ut tempor odio. Cras mauris nisl,
    rhoncus et tortor id, lobortis ornare libero. Vivamus tellus eros,
    semper sit amet gravida ac, pellentesque a tortor. Etiam
    sollicitudin mauris vitae purus pellentesque, sit amet elementum
    lacus suscipit. Duis id felis sed justo placerat facilisis
    convallis id purus. Praesent fermentum, odio vel varius
    scelerisque, arcu tortor sagittis nisi, et egestas turpis sem vitae
    orci. Maecenas hendrerit nulla ultrices nulla porttitor, nec
    tincidunt odio cursus.\n\n
    Curabitur et diam nisi. In hac habitasse platea dictumst. Aliquam
    suscipit, arcu nec lobortis ultricies, arcu nibh varius tortor, sit
    amet rhoncus mauris ante id augue. Nullam est justo, sodales vitae
    nunc eget, mattis laoreet arcu. Curabitur pharetra hendrerit
    turpis, in placerat elit accumsan eu. Curabitur vulputate lacus in
    leo scelerisque condimentum. Sed sodales justo sit amet porta
    scelerisque. Sed metus urna, tempor ut pellentesque ut, fermentum
    in ligula. Pellentesque ullamcorper, nisi non tempus congue, massa
    ligula vestibulum nulla, sit amet cursus ligula felis et purus.
    Suspendisse eleifend ante nibh, eget aliquam neque suscipit ac. In
    hac habitasse platea dictumst. Praesent tempus faucibus enim nec
    hendrerit. Nullam non purus vel lacus dignissim cursus eu ac est.
    Nulla tellus mauris, maximus sed faucibus non, egestas quis eros.
    Ut massa purus, luctus non ex tempor, suscipit efficitur mi.
    """
    Then the short description of this event in CDBXML equals:
    """
 	Korte beschrijving - Lorem ipsum dolor sit amet, consectetur
    adipiscing elit: Donec non velit eu eros eleifend mattis. Mauris
    tristique scelerisque consectetur. Morbi a congue purus, quis
    tempor arcu. Nam bibendum risus vel nulla feugiat finibus. Aenean
    vestibulum nisi vel nisl elementum, quis faucibus ex dictum
    nullam. ...
 	"""
    # korte beschrijving afkappen zodat deze max 396 karakters bevat: + ' ...' (= max 400)
    # op laatste leesteken[.|?|!] voor de limiet of laatste spatie voor de limiet?

    And the long description of this event in CDBXML equals:
    """
    Korte beschrijving - Lorem ipsum dolor sit amet, consectetur
    adipiscing elit: Donec non velit eu eros eleifend mattis. Mauris
    tristique scelerisque consectetur. Morbi a congue purus, quis
    tempor arcu. Nam bibendum risus vel nulla feugiat finibus. Aenean
    vestibulum nisi vel nisl elementum, quis faucibus ex dictum
    nullam.<br><br>
    Lange Beschrijving - Donec porta molestie arcu, ut tempor odio. Cras mauris nisl,
    rhoncus et tortor id, lobortis ornare libero. Vivamus tellus eros,
    semper sit amet gravida ac, pellentesque a tortor. Etiam
    sollicitudin mauris vitae purus pellentesque, sit amet elementum
    lacus suscipit. Duis id felis sed justo placerat facilisis
    convallis id purus. Praesent fermentum, odio vel varius
    scelerisque, arcu tortor sagittis nisi, et egestas turpis sem vitae
    orci. Maecenas hendrerit nulla ultrices nulla porttitor, nec
    tincidunt odio cursus.<br><br>
    Curabitur et diam nisi. In hac habitasse platea dictumst. Aliquam
    suscipit, arcu nec lobortis ultricies, arcu nibh varius tortor, sit
    amet rhoncus mauris ante id augue. Nullam est justo, sodales vitae
    nunc eget, mattis laoreet arcu. Curabitur pharetra hendrerit
    turpis, in placerat elit accumsan eu. Curabitur vulputate lacus in
    leo scelerisque condimentum. Sed sodales justo sit amet porta
    scelerisque. Sed metus urna, tempor ut pellentesque ut, fermentum
    in ligula. Pellentesque ullamcorper, nisi non tempus congue, massa
    ligula vestibulum nulla, sit amet cursus ligula felis et purus.
    Suspendisse eleifend ante nibh, eget aliquam neque suscipit ac. In
    hac habitasse platea dictumst. Praesent tempus faucibus enim nec
    hendrerit. Nullam non purus vel lacus dignissim cursus eu ac est.
    Nulla tellus mauris, maximus sed faucibus non, egestas quis eros.
    Ut massa purus, luctus non ex tempor, suscipit efficitur mi.<br>
    <p class="uiv-source">Bron: <a href="http://www.uitinvlaanderen.be/agenda/e/een-piano-in-de-tu-n-joodse-rituelen-en-gebruiken/3aee552e-2071-46a1-beff-d73b31718ea6">UiTinVlaanderen.be</a></p>
    """
    # beschrijving niet genest in p-tags
    # \n omgezet naar <br>
    # lange beschrijving is volledige beschrijving van UDB3