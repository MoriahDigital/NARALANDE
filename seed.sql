USE naralande;

-- 1. Formations
INSERT INTO formations (user_id, title, school_name, location, type, description) VALUES
(3, 'Formation Intensive en Développement Web', 'NARALANDE Academy', 'En Ligne', 'online', 'Devenez développeur web Full-Stack en 6 mois. Maîtrisez HTML, CSS, JavaScript, PHP et MySQL. Idéal pour les débutants ambitieux !'),
(3, 'Maîtrise de Microsoft Excel (De Débutant à Expert)', 'Centre de Formation Professionnelle', 'Conakry', 'offline', 'Formation pratique de 2 semaines sur Excel. Apprenez les tableaux croisés dynamiques, les macros, et les formules complexes pour booster votre employabilité.');

-- 2. Emplois
INSERT INTO jobs (user_id, title, company, location, salary, description) VALUES
(3, 'Développeur Full-Stack Junior', 'Bahtech', 'Conakry', 'À débattre', 'Bahtech recherche un développeur passionné pour rejoindre son équipe technique.\n\nMissions :\n- Création et maintenance d\'applications web.\n- Collaboration avec l\'équipe design.\n\nProfil recherché :\n- Bonne connaissance de PHP et JavaScript.\n- Esprit d\'équipe et curiosité d\'apprendre.');

-- 3. Boutique (Marketplace)
INSERT INTO marketplace (user_id, name, description, price) VALUES
(3, 'Ordinateur HP Core i5 - Très Bon État', 'Vends Ordinateur portable HP Core i5, 8Go de RAM, 256Go SSD. Parfait pour la bureautique et le développement. Batterie en excellent état (tient 4h).', 2700000.00);

-- 4. Conseils & Carrière
INSERT INTO articles (user_id, title, category, content) VALUES
(3, 'Comment ne pas abandonner quand tout semble difficile', 'vie_pratique', 'Parfois, face aux défis professionnels ou aux apprentissages compliqués (comme apprendre à coder !), l\'envie de tout abandonner est forte.\n\nVoici 3 conseils pour tenir le cap :\n\n1. Regardez le chemin parcouru : Ne vous concentrez pas uniquement sur ce qu\'il reste à faire, mais célébrez chaque petite victoire. Vous en savez déjà bien plus qu\'hier.\n\n2. Faites une vraie pause : L\'épuisement mental fausse notre jugement. Éloignez-vous de l\'écran, dormez, marchez. La solution vient souvent quand le cerveau se repose.\n\n3. Parlez-en : Partagez vos doutes avec la communauté NARALANDE. Beaucoup sont passés par là et peuvent vous apporter le soutien nécessaire.\n\nN\'oubliez jamais pourquoi vous avez commencé ! Courage !');
