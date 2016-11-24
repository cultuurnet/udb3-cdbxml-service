Feature: add sameAs url as source to long description.
  Scenario: add sameAs as source to long description in cdbxml when adding an event
    Given nothing
    When I add an event in UDB3 with the following cdbid
    """
    d95894c7-bfd7-43e1-8939-b2a4423a882a
    """
    And the following title
    """
    Lorem ipsum dolor
    """
    And the following description:
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
    tincidunt odio cursus.
    """
    And the following sameAs:
    """
    http://www.uitinvlaanderen.be/agenda/e/lorem-ipsum-dolor/d95894c7-bfd7-43e1-8939-b2a4423a882a
    """
    Then the long description in CDBXML should be
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
    tincidunt odio cursus.
    <p class="uiv-source">Bron: <a href="http://www.uitinvlaanderen.be/agenda/e/lorem-ipsum-dolor/d95894c7-bfd7-43e1-8939-b2a4423a882a">UiTinVlaanderen.be</a></p>
    """

  Scenario: add sameAs url as source to long description when editing major info of an event
    Given an event in UDB3 with the following cdbid
    """
    d95894c7-bfd7-43e1-8939-b2a4423a882a
    """
    When I change the title of the event to
    """
    Ik heb nu een andere titel
    """
    Then the long description in CDBXML should be
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
    tincidunt odio cursus.
    <p class="uiv-source">Bron: <a href="http://www.uitinvlaanderen.be/agenda/e/ik-heb-nu-een-andere-titel/d95894c7-bfd7-43e1-8939-b2a4423a882a">UiTinVlaanderen.be</a></p>
    """

  Scenario: add sameAs url as source to long description when editing the description of an event
    Given an event in UDB3 with the following cdbid
    """
    d95894c7-bfd7-43e1-8939-b2a4423a882a
    """
    And the following title
    """
    Lorem ipsum dolor
    """
    When I change the description of the event to
    """
    Donec pharetra, ipsum non eleifend tincidunt, lorem leo gravida
    magna, eu consectetur purus justo lacinia erat. Mauris ac nisi
    eu elit ultrices condimentum ac sit amet neque. Curabitur ultricies sollicitudin
    condimentum. Sed hendrerit lacus ut iaculis gravida. Suspendisse euismod,
    massa vel elementum faucibus, risus nibh blandit eros, eget lacinia nisl lorem
    cursus nisl. Nunc ornare gravida sem ut tempus. Nam vel dapibus purus,
    et finibus odio. Suspendisse nec ex eu lorem cursus ornare ut in turpis.
    Vivamus quis arcu vulputate, semper nibh ullamcorper, rhoncus odio.
    Curabitur elementum laoreet scelerisque. Curabitur eget commodo lacus, et
    sollicitudin tortor. Suspendisse ultricies quis urna id maximus.\n\n
    Nulla iaculis convallis augue sit amet cursus. Duis maximus risus a nibh
    consequat, at pulvinar mauris fermentum. Vivamus pellentesque libero quis
    dolor venenatis pellentesque. Morbi in dolor ligula. Fusce ac leo iaculis,
    fermentum nulla at, pharetra lorem. Pellentesque id ante maximus, tincidunt
    tellus eu, pharetra lacus. Nulla facilisi. Phasellus dictum molestie faucibus.
    Curabitur volutpat volutpat purus ut congue. Ut nec velit interdum, faucibus
    velit in, efficitur nisl. Phasellus scelerisque ligula imperdiet interdum bibendum.
    """
    Then the long description in CDBXML should be
    """
    Donec pharetra, ipsum non eleifend tincidunt, lorem leo gravida
    magna, eu consectetur purus justo lacinia erat. Mauris ac nisi
    eu elit ultrices condimentum ac sit amet neque. Curabitur ultricies sollicitudin
    condimentum. Sed hendrerit lacus ut iaculis gravida. Suspendisse euismod,
    massa vel elementum faucibus, risus nibh blandit eros, eget lacinia nisl lorem
    cursus nisl. Nunc ornare gravida sem ut tempus. Nam vel dapibus purus,
    et finibus odio. Suspendisse nec ex eu lorem cursus ornare ut in turpis.
    Vivamus quis arcu vulputate, semper nibh ullamcorper, rhoncus odio.
    Curabitur elementum laoreet scelerisque. Curabitur eget commodo lacus, et
    sollicitudin tortor. Suspendisse ultricies quis urna id maximus.<br><br>
    Nulla iaculis convallis augue sit amet cursus. Duis maximus risus a nibh
    consequat, at pulvinar mauris fermentum. Vivamus pellentesque libero quis
    dolor venenatis pellentesque. Morbi in dolor ligula. Fusce ac leo iaculis,
    fermentum nulla at, pharetra lorem. Pellentesque id ante maximus, tincidunt
    tellus eu, pharetra lacus. Nulla facilisi. Phasellus dictum molestie faucibus.
    Curabitur volutpat volutpat purus ut congue. Ut nec velit interdum, faucibus
    velit in, efficitur nisl. Phasellus scelerisque ligula imperdiet interdum bibendum.
    <p class="uiv-source">Bron: <a href="http://www.uitinvlaanderen.be/agenda/e/lorem-ipsum-dolor/d95894c7-bfd7-43e1-8939-b2a4423a882a">UiTinVlaanderen.be</a></p>
    """