-- ============================================================
--  SEB-LMS-portal  —  Database v3  (Complete Enhanced Version)
--  8 Subjects | 4 Modules Each | Rich Q&A | Strict Exam Mode
-- ============================================================
DROP DATABASE IF EXISTS seb_lms;
CREATE DATABASE seb_lms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE seb_lms;

CREATE TABLE students (
  id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL,
  regno VARCHAR(50) NOT NULL UNIQUE, password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
INSERT INTO students (name,regno,password) VALUES
('Demo Student','demo','demo'),('Arun Kumar','2023CS001','pass123'),
('Priya Devi','2023CS002','pass123'),('Rahul Singh','2023CS003','pass123'),
('Deepa Mohan','2023CS004','pass123'),('Karthik Raja','2023CS005','pass123');

CREATE TABLE admin_users (
  id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(80) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL
) ENGINE=InnoDB;
INSERT INTO admin_users (username,password) VALUES
('admin','$2y$10$u8OFjMSnIaOvVjSXMRxBsOjOSgMBf0A7JZ6oWHC7WZdEIBBpJLiuS');

CREATE TABLE subjects (
  id INT AUTO_INCREMENT PRIMARY KEY, subject_name VARCHAR(100) NOT NULL,
  description VARCHAR(255), icon VARCHAR(10) DEFAULT '📚',
  color VARCHAR(80) DEFAULT 'linear-gradient(135deg,#43e97b,#38f9d7)',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
INSERT INTO subjects (subject_name,description,icon,color) VALUES
('Java','Object-Oriented Programming, Collections, Threads','coffee','linear-gradient(135deg,#f7971e,#ffd200)'),
('Python','Python programming from basics to advanced','snake','linear-gradient(135deg,#43e97b,#38f9d7)'),
('C Programming','Loops, arrays, pointers, memory management','gear','linear-gradient(135deg,#74b9ff,#a29bfe)'),
('Data Structures and Algorithms','Arrays, Stacks, Queues, Trees, Graphs','structure','linear-gradient(135deg,#f9d423,#f7971e)'),
('Advanced DSA','AVL Trees, Segment Trees, Tries, DP','rocket','linear-gradient(135deg,#ff6b81,#ee5a24)'),
('Database Management System','SQL, Normalization, Transactions, Indexing','database','linear-gradient(135deg,#a855f7,#7c3aed)'),
('Design and Analysis of Algorithm','Complexity, Divide and Conquer, Greedy, DP','compass','linear-gradient(135deg,#11998e,#38ef7d)'),
('Placement Training','Aptitude, Logical Reasoning, Coding Rounds','target','linear-gradient(135deg,#2563eb,#38bdf8)');

CREATE TABLE questions (
  id INT AUTO_INCREMENT PRIMARY KEY, subject_id INT NOT NULL,
  title VARCHAR(255) NOT NULL, question TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE testcases (
  id INT AUTO_INCREMENT PRIMARY KEY, question_id INT NOT NULL,
  input TEXT, expected_output TEXT NOT NULL,
  FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE user_progress (
  id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, question_id INT NOT NULL,
  status ENUM('not_started','attempted','solved') DEFAULT 'not_started',
  submitted_code LONGTEXT, score INT DEFAULT 0,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_question (user_id,question_id),
  FOREIGN KEY (user_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE modules (
  id INT AUTO_INCREMENT PRIMARY KEY, subject_id INT NOT NULL,
  title VARCHAR(100) NOT NULL, description VARCHAR(255),
  order_num INT DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE module_questions (
  id INT AUTO_INCREMENT PRIMARY KEY, module_id INT NOT NULL, question_id INT NOT NULL,
  order_num INT DEFAULT 1,
  UNIQUE KEY uq_mod_q (module_id,question_id),
  FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
  FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE module_progress (
  id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, module_id INT NOT NULL,
  status ENUM('locked','unlocked','completed') DEFAULT 'locked',
  start_time TIMESTAMP NULL, end_time TIMESTAMP NULL,
  time_taken_sec INT DEFAULT 0, marks_obtained INT DEFAULT 0, total_marks INT DEFAULT 0,
  UNIQUE KEY uq_user_module (user_id,module_id),
  FOREIGN KEY (user_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE exams (
  id INT AUTO_INCREMENT PRIMARY KEY, subject_id INT,
  title VARCHAR(255) NOT NULL, duration_min INT DEFAULT 90,
  total_marks INT DEFAULT 100, is_active TINYINT(1) DEFAULT 1,
  entry_password VARCHAR(100) DEFAULT '',
  exit_password VARCHAR(100) DEFAULT 'quit2024',
  start_time DATETIME NULL, end_time DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE exam_mcq_questions (
  id INT AUTO_INCREMENT PRIMARY KEY, exam_id INT NOT NULL,
  question TEXT NOT NULL,
  opt_a VARCHAR(255) NOT NULL, opt_b VARCHAR(255) NOT NULL,
  opt_c VARCHAR(255) NOT NULL, opt_d VARCHAR(255) NOT NULL,
  answer CHAR(1) NOT NULL, marks INT DEFAULT 1, order_num INT DEFAULT 1,
  FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE exam_coding_questions (
  id INT AUTO_INCREMENT PRIMARY KEY, exam_id INT NOT NULL, question_id INT NOT NULL,
  part ENUM('B','C') DEFAULT 'B', marks INT DEFAULT 7, order_num INT DEFAULT 1,
  FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
  FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE exam_attempts (
  id INT AUTO_INCREMENT PRIMARY KEY, exam_id INT NOT NULL, student_id INT NOT NULL,
  started_at DATETIME DEFAULT NULL, submitted TINYINT(1) DEFAULT 0,
  finished_at DATETIME DEFAULT NULL,
  score_mcq INT DEFAULT 0, score_code INT DEFAULT 0,
  total_score INT DEFAULT 0, max_marks INT DEFAULT 0,
  UNIQUE KEY uq_exam_student (exam_id,student_id),
  FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE exam_mcq_answers (
  id INT AUTO_INCREMENT PRIMARY KEY, attempt_id INT NOT NULL, mcq_id INT NOT NULL,
  chosen CHAR(1) DEFAULT NULL, is_correct TINYINT(1) DEFAULT 0,
  UNIQUE KEY uq_attempt_mcq (attempt_id,mcq_id),
  FOREIGN KEY (attempt_id) REFERENCES exam_attempts(id) ON DELETE CASCADE,
  FOREIGN KEY (mcq_id) REFERENCES exam_mcq_questions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE exam_code_answers (
  id INT AUTO_INCREMENT PRIMARY KEY, attempt_id INT NOT NULL, ecq_id INT NOT NULL,
  language VARCHAR(20) DEFAULT 'python', submitted_code LONGTEXT,
  passed TINYINT(1) DEFAULT 0, score INT DEFAULT 0,
  UNIQUE KEY uq_attempt_ecq (attempt_id,ecq_id),
  FOREIGN KEY (attempt_id) REFERENCES exam_attempts(id) ON DELETE CASCADE,
  FOREIGN KEY (ecq_id) REFERENCES exam_coding_questions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
--  QUESTIONS: Java (subject 1) - 16 questions
-- ============================================================
INSERT INTO questions (subject_id,title,question) VALUES
(1,'Hello World in Java','Print Hello, World! to the console.\n\nInput: none\nOutput: Hello, World!'),
(1,'Sum of Two Numbers','Read two integers A and B. Print their sum.\n\nInput: two space-separated integers A B\nOutput: sum'),
(1,'Even or Odd','Read integer N. Print Even or Odd.\n\nInput: integer N\nOutput: Even or Odd'),
(1,'Swap Two Numbers','Read two integers A and B. Print them swapped.\n\nInput: A B\nOutput: B A'),
(1,'Reverse a String','Read a string. Print it reversed.\n\nInput: single word\nOutput: reversed word'),
(1,'Count Vowels','Read a string. Print count of vowels.\n\nInput: string\nOutput: vowel count'),
(1,'Array Sum','Read N then N integers. Print their sum.\n\nInput: N then N integers\nOutput: sum'),
(1,'Find Maximum','Read N then N integers. Print maximum.\n\nInput: N then N integers\nOutput: maximum'),
(1,'Factorial','Read N (0<=N<=12). Print N factorial.\n\nInput: N\nOutput: factorial'),
(1,'Fibonacci Series','Read N. Print first N Fibonacci numbers space-separated.\n\nInput: N\nOutput: Fibonacci numbers'),
(1,'Check Prime','Read N. Print Prime or Not Prime.\n\nInput: N\nOutput: Prime or Not Prime'),
(1,'Power of Two','Read N. Print Yes if N is a power of 2, else No.\n\nInput: N\nOutput: Yes or No'),
(1,'Binary Search','Read N sorted integers then target T. Print 0-based index or -1.\n\nInput: N, N sorted ints, T\nOutput: index or -1'),
(1,'Bubble Sort','Read N then N integers. Print sorted ascending.\n\nInput: N then N integers\nOutput: sorted space-separated'),
(1,'Count Words','Read a sentence. Print word count.\n\nInput: sentence\nOutput: count'),
(1,'Palindrome Check','Read a string. Print Palindrome or Not Palindrome.\n\nInput: string\nOutput: Palindrome or Not Palindrome');

-- Python (subject 2) - 16 questions
INSERT INTO questions (subject_id,title,question) VALUES
(2,'Hello World','Print Hello, World!\n\nInput: none\nOutput: Hello, World!'),
(2,'Sum of Two Numbers','Read two integers A B. Print sum.\n\nInput: A B\nOutput: sum'),
(2,'Even or Odd','Read N. Print Even or Odd.\n\nInput: N\nOutput: Even or Odd'),
(2,'Maximum of Three','Read three integers. Print maximum.\n\nInput: three ints\nOutput: maximum'),
(2,'Reverse String','Read a word. Print it reversed.\n\nInput: word\nOutput: reversed'),
(2,'Count Characters','Read string. Print length.\n\nInput: string\nOutput: length'),
(2,'Upper Case','Read string. Print it in UPPERCASE.\n\nInput: string\nOutput: uppercase'),
(2,'Word Count','Read sentence. Print word count.\n\nInput: sentence\nOutput: count'),
(2,'Factorial','Read N (0<=N<=12). Print N!\n\nInput: N\nOutput: factorial'),
(2,'Check Prime','Read N. Print Prime or Not Prime.\n\nInput: N\nOutput: Prime or Not Prime'),
(2,'Fibonacci N Terms','Read N. Print first N Fibonacci numbers one per line.\n\nInput: N\nOutput: N lines'),
(2,'Sum of Digits','Read N. Print sum of digits.\n\nInput: N\nOutput: digit sum'),
(2,'List Sum','Read N then N integers. Print sum.\n\nInput: N then N integers\nOutput: sum'),
(2,'Sort List','Read N then N integers. Print sorted ascending.\n\nInput: N then N\nOutput: sorted'),
(2,'Remove Duplicates','Read N then N integers. Print unique elements in order of first appearance.\n\nInput: N then N\nOutput: unique space-separated'),
(2,'Matrix Transpose','Read N then NxN matrix. Print transpose.\n\nInput: N then N rows\nOutput: transposed matrix');

-- C Programming (subject 3) - 16 questions
INSERT INTO questions (subject_id,title,question) VALUES
(3,'Hello World','Print Hello, World!\n\nInput: none\nOutput: Hello, World!'),
(3,'Sum of Two Numbers','Read A B. Print sum.\n\nInput: A B\nOutput: sum'),
(3,'Even or Odd','Read N. Print Even or Odd.\n\nInput: N\nOutput: Even or Odd'),
(3,'Simple Calculator','Read A op B (op=+,-,*). Print result.\n\nInput: A op B\nOutput: result'),
(3,'Factorial','Read N (0<=N<=12). Print N!\n\nInput: N\nOutput: factorial'),
(3,'Sum of Digits','Read N. Print digit sum.\n\nInput: N\nOutput: sum'),
(3,'Reverse Number','Read integer. Print reversed digits.\n\nInput: integer\nOutput: reversed'),
(3,'Multiplication Table','Read N. Print multiplication table 1 to 10.\n\nInput: N\nOutput: 10 lines N x i = result'),
(3,'Array Sum','Read N then N integers. Print sum.\n\nInput: N then N\nOutput: sum'),
(3,'Max of Array','Read N then N integers. Print max.\n\nInput: N then N\nOutput: max'),
(3,'Min of Array','Read N then N integers. Print min.\n\nInput: N then N\nOutput: min'),
(3,'Array Reverse','Read N then N integers. Print reversed.\n\nInput: N then N\nOutput: reversed space-separated'),
(3,'String Length','Read string. Print length without strlen.\n\nInput: string\nOutput: length'),
(3,'Check Prime','Read N. Print Prime or Not Prime.\n\nInput: N\nOutput: Prime or Not Prime'),
(3,'Fibonacci','Read N. Print first N Fibonacci numbers space-separated.\n\nInput: N\nOutput: N numbers'),
(3,'Palindrome Number','Read N. Print Palindrome or Not Palindrome.\n\nInput: N\nOutput: result');

-- DSA (subject 4) - 16 questions
INSERT INTO questions (subject_id,title,question) VALUES
(4,'Linear Search','Read N integers and target T. Print 0-based index or -1.\n\nInput: N, N ints, T\nOutput: index or -1'),
(4,'Binary Search','Read N sorted integers and T. Print index or -1.\n\nInput: N, N sorted ints, T\nOutput: index or -1'),
(4,'Find Second Max','Read N integers. Print second maximum.\n\nInput: N then N ints\nOutput: second max'),
(4,'Count Occurrences','Read N integers and target T. Print count of T.\n\nInput: N then N ints then T\nOutput: count'),
(4,'Bubble Sort','Read N then N integers. Print sorted.\n\nInput: N then N\nOutput: sorted'),
(4,'Selection Sort','Read N then N integers. Print sorted.\n\nInput: N then N\nOutput: sorted'),
(4,'Insertion Sort','Read N then N integers. Print sorted.\n\nInput: N then N\nOutput: sorted'),
(4,'Merge Sort','Read N then N integers. Print sorted using merge sort.\n\nInput: N then N\nOutput: sorted'),
(4,'Stack Push Pop','Read N ops: push X or pop. Print popped values or Empty.\n\nInput: N then ops\nOutput: one line per pop'),
(4,'Balanced Parentheses','Read bracket string. Print Balanced or Unbalanced.\n\nInput: brackets\nOutput: result'),
(4,'Queue Simulation','Read N ops: enqueue X or dequeue. Print dequeued or Empty.\n\nInput: N then ops\nOutput: one per dequeue'),
(4,'Reverse Stack','Read N integers (stack, leftmost=top). Print reversed.\n\nInput: N then N\nOutput: reversed'),
(4,'Linked List Sum','Read N integers. Print sum.\n\nInput: N then N\nOutput: sum'),
(4,'Tree Height','Read N-1 edges. Print tree height.\n\nInput: N, N-1 edges parent child\nOutput: height'),
(4,'Inorder BST','Insert N values into BST. Print inorder.\n\nInput: N then N values\nOutput: sorted inorder'),
(4,'Count Leaf Nodes','Read tree edges. Print leaf count.\n\nInput: N, N-1 edges u v\nOutput: leaf count');

-- Advanced DSA (subject 5) - 16 questions
INSERT INTO questions (subject_id,title,question) VALUES
(5,'Fibonacci DP','Read N. Print Nth Fibonacci (F(0)=0,F(1)=1).\n\nInput: N\nOutput: F(N)'),
(5,'0/1 Knapsack','Read W, N items then N weight-value pairs. Print max value.\n\nInput: W, N, pairs\nOutput: max value'),
(5,'LCS Length','Read two strings. Print LCS length.\n\nInput: two strings\nOutput: LCS length'),
(5,'Coin Change','Read N coins then amount S. Print min coins or -1.\n\nInput: N, coins, S\nOutput: min or -1'),
(5,'BFS Traversal','Read N M graph, M edges, start S. Print BFS.\n\nInput: N M, edges, S\nOutput: BFS order'),
(5,'DFS Traversal','Read N M graph, M edges, start S. Print DFS.\n\nInput: N M, edges, S\nOutput: DFS order'),
(5,'Cycle Detection','Read N M undirected graph. Print Cycle or No Cycle.\n\nInput: N M, M edges\nOutput: result'),
(5,'Shortest Path BFS','Read N M graph, edges, S D. Print distance or -1.\n\nInput: N M, edges, S D\nOutput: distance'),
(5,'AVL Insert Count','Read N values. Print rotation count during AVL insertions.\n\nInput: N then N values\nOutput: rotations'),
(5,'LCA in BST','Insert N values into BST. Answer Q LCA queries.\n\nInput: N values, Q, Q queries\nOutput: Q LCAs'),
(5,'Level Order Sum','Read tree. Print sum at each level.\n\nInput: N then N values (level order)\nOutput: sums per level'),
(5,'Tree Diameter','Read N-1 edges. Print diameter.\n\nInput: N, edges\nOutput: diameter'),
(5,'Trie Search','Read N words then Q queries. Print Found or Not Found.\n\nInput: N words, Q, queries\nOutput: Q results'),
(5,'Range Sum Query','Read N ints then Q range queries l r. Print sums.\n\nInput: N ints, Q, l r pairs\nOutput: Q sums'),
(5,'Sliding Window Max','Read N ints and K. Print max in each window.\n\nInput: N K then N ints\nOutput: maxima'),
(5,'Topological Sort','Read N M DAG. Print valid topological order.\n\nInput: N M, M edges\nOutput: topo order');

-- DBMS (subject 6) - 16 questions
INSERT INTO questions (subject_id,title,question) VALUES
(6,'Count Simulation','Given count is always 5. Print it.\n\nInput: none\nOutput: 5'),
(6,'Sort Names','Read N names. Print sorted alphabetically.\n\nInput: N, N names\nOutput: sorted names'),
(6,'Filter Even IDs','Read N IDs. Print those divisible by 2.\n\nInput: N then N ints\nOutput: even IDs'),
(6,'Aggregate SUM','Read N salary values. Print sum.\n\nInput: N then N ints\nOutput: sum'),
(6,'JOIN Simulation','Read N pairs id-nameA and M pairs id-nameB. Print matched.\n\nInput: N pairs, M pairs\nOutput: matched pairs'),
(6,'Distinct Values','Read N ints. Print distinct in appearance order.\n\nInput: N then N\nOutput: distinct'),
(6,'Group Count','Read N strings. Print each unique string with count.\n\nInput: N strings\nOutput: string count per line'),
(6,'Max per Group','Read N group-value pairs. Print max value per group.\n\nInput: N pairs\nOutput: group max'),
(6,'FD Closure','Read attributes, FDs and query set X. Print closure X+.\n\nInput: R, F FDs, X set\nOutput: X+ sorted'),
(6,'Candidate Keys','Read attributes and FDs. Print attributes that are candidate keys.\n\nInput: N attrs, M FDs\nOutput: keys'),
(6,'3NF Check','Read N FDs. Count those violating 3NF.\n\nInput: N, N FDs\nOutput: violation count'),
(6,'Armstrong Axioms','Read two FDs A->B, B->C. Print Yes if A->C holds.\n\nInput: two FDs\nOutput: Yes or No'),
(6,'Transaction Conflict','Read N T1/T2 operations. Print Conflict or No Conflict.\n\nInput: N ops\nOutput: result'),
(6,'B-Tree Height','Read order M and N keys. Print B-tree height.\n\nInput: M, N, N keys\nOutput: height'),
(6,'Deadlock Detection','Read N wait-for edges Ti->Tj. Print Deadlock or No Deadlock.\n\nInput: N, edges\nOutput: result'),
(6,'Dirty Read Check','Read operation sequence. Print Dirty Read or Clean.\n\nInput: ops\nOutput: result');

-- DAA (subject 7) - 16 questions
INSERT INTO questions (subject_id,title,question) VALUES
(7,'Complexity Class','Read N and F(N). Classify complexity.\n\nInput: N F\nOutput: O(N) etc'),
(7,'Binary Search DAA','Read N sorted ints and T. Print index or -1.\n\nInput: N, ints, T\nOutput: index'),
(7,'Merge Sort DAA','Read N ints. Print sorted.\n\nInput: N then N\nOutput: sorted'),
(7,'Max Subarray','Read N ints. Print max subarray sum.\n\nInput: N then N\nOutput: max sum'),
(7,'Activity Selection','Read N activities start end. Print max non-overlapping count.\n\nInput: N, N start-end pairs\nOutput: count'),
(7,'Fractional Knapsack','Read W, N items weight-value. Print max value (2 dec).\n\nInput: W, N, N pairs\nOutput: value'),
(7,'Huffman Cost','Read N chars and frequencies. Print total encoding cost.\n\nInput: N, N char-freq pairs\nOutput: Total Cost: X'),
(7,'MST Kruskal','Read N M graph with weights. Print MST weight.\n\nInput: N M, M edges u v w\nOutput: MST weight'),
(7,'LCS DAA','Read two strings. Print LCS length.\n\nInput: two strings\nOutput: length'),
(7,'Matrix Chain','Read N+1 dimensions. Print min multiplications.\n\nInput: N+1 values\nOutput: min ops'),
(7,'Edit Distance','Read two strings. Print edit distance.\n\nInput: two strings\nOutput: distance'),
(7,'Rod Cutting','Read rod length N and N prices. Print max revenue.\n\nInput: N, N prices\nOutput: revenue'),
(7,'Dijkstra','Read N M weighted graph and source S. Print distances.\n\nInput: N M, M edges, S\nOutput: N distances'),
(7,'Floyd Warshall','Read N nodes and NxN matrix. Print all-pairs shortest.\n\nInput: N, NxN matrix\nOutput: NxN result'),
(7,'Topological Sort DAG','Read N M DAG. Print topological order.\n\nInput: N M, M edges\nOutput: topo order'),
(7,'Graph Coloring','Read N M K. Print Possible or Not Possible.\n\nInput: N M K, M edges\nOutput: result');

-- Placement Training (subject 8) - 16 questions
INSERT INTO questions (subject_id,title,question) VALUES
(8,'GCD','Read A B. Print GCD.\n\nInput: A B\nOutput: GCD'),
(8,'LCM','Read A B. Print LCM.\n\nInput: A B\nOutput: LCM'),
(8,'Count Primes','Read N. Print count of primes from 1 to N.\n\nInput: N\nOutput: count'),
(8,'Perfect Number','Read N. Print Perfect or Not Perfect.\n\nInput: N\nOutput: result'),
(8,'Harmonic Series','Read N. Print sum 1+1/2+...+1/N to 2 decimals.\n\nInput: N\nOutput: sum'),
(8,'Digit Count','Read N. Print number of digits.\n\nInput: N\nOutput: count'),
(8,'Armstrong Number','Read N. Print Armstrong or Not Armstrong.\n\nInput: N\nOutput: result'),
(8,'Decimal to Binary','Read decimal N. Print binary.\n\nInput: N\nOutput: binary'),
(8,'Anagram Check','Read two strings. Print Anagram or Not Anagram.\n\nInput: two strings\nOutput: result'),
(8,'Run Length Encoding','Read string. Print run-length encoded e.g. aaabb -> a3b2.\n\nInput: string\nOutput: encoded'),
(8,'Missing Number','Read N-1 ints from 1..N. Print missing.\n\nInput: N, N-1 ints\nOutput: missing'),
(8,'Rotate Array','Read N ints and K. Print right-rotated by K.\n\nInput: N K, N ints\nOutput: rotated'),
(8,'Two Sum','Read N ints and target T. Print indices of pair summing to T.\n\nInput: N, N ints, T\nOutput: i j'),
(8,'Trapping Rain Water','Read N heights. Print total trapped water.\n\nInput: N, N heights\nOutput: water'),
(8,'Valid Brackets','Read ()[]{}. Print Valid or Invalid.\n\nInput: bracket string\nOutput: result'),
(8,'Spiral Matrix','Read N. Print NxN spiral matrix.\n\nInput: N\nOutput: NxN matrix');

-- ============================================================
-- TEST CASES (representative samples per question)
-- ============================================================
-- Java Q1-16
INSERT INTO testcases (question_id,input,expected_output) VALUES
(1,'','Hello, World!'),(2,'3 7','10'),(2,'10 20','30'),
(3,'4','Even'),(3,'7','Odd'),(4,'3 5','5 3'),(4,'10 20','20 10'),
(5,'hello','olleh'),(5,'java','avaj'),(6,'Hello World','3'),(6,'aeiou','5'),
(7,'4\n1 2 3 4','10'),(7,'5\n5 5 5 5 5','25'),(8,'5\n3 1 4 1 5','5'),(8,'4\n10 2 3 8','10'),
(9,'5','120'),(9,'0','1'),(9,'7','5040'),(10,'6','0 1 1 2 3 5'),(10,'4','0 1 1 2'),
(11,'7','Prime'),(11,'4','Not Prime'),(11,'1','Not Prime'),
(12,'4','Yes'),(12,'8','Yes'),(12,'6','No'),
(13,'5\n1 3 5 7 9\n5','2'),(13,'5\n1 3 5 7 9\n4','-1'),
(14,'5\n5 3 1 4 2','1 2 3 4 5'),(14,'4\n4 3 2 1','1 2 3 4'),
(15,'Hello World Python','3'),(15,'one two three four','4'),
(16,'racecar','Palindrome'),(16,'hello','Not Palindrome');

-- Python Q17-32
INSERT INTO testcases (question_id,input,expected_output) VALUES
(17,'','Hello, World!'),(18,'3 7','10'),(18,'10 20','30'),
(19,'4','Even'),(19,'7','Odd'),(20,'3 5 2','5'),(20,'10 20 15','20'),
(21,'hello','olleh'),(21,'python','nohtyp'),(22,'hello','5'),(22,'programming','11'),
(23,'hello','HELLO'),(23,'world','WORLD'),(24,'hello world','2'),(24,'one two three four','4'),
(25,'5','120'),(25,'0','1'),(26,'7','Prime'),(26,'4','Not Prime'),
(27,'6','0\n1\n1\n2\n3\n5'),(27,'4','0\n1\n1\n2'),(28,'123','6'),(28,'999','27'),
(29,'4\n1 2 3 4','10'),(29,'5\n5 5 5 5 5','25'),
(30,'5\n3 1 4 1 5','1 3 4 5'),(30,'4\n4 3 2 1','1 2 3 4'),
(31,'5\n1 2 2 3 3','1 2 3'),(31,'4\n4 4 4 4','4'),
(32,'2\n1 2\n3 4','1 3\n2 4');

-- C Q33-48
INSERT INTO testcases (question_id,input,expected_output) VALUES
(33,'','Hello, World!'),(34,'3 7','10'),(34,'5 5','10'),
(35,'4','Even'),(35,'7','Odd'),(36,'10 + 5','15'),(36,'10 - 3','7'),(36,'4 * 6','24'),
(37,'5','120'),(37,'0','1'),(38,'123','6'),(38,'456','15'),
(39,'123','321'),(39,'1200','21'),
(40,'3','3 x 1 = 3\n3 x 2 = 6\n3 x 3 = 9\n3 x 4 = 12\n3 x 5 = 15\n3 x 6 = 18\n3 x 7 = 21\n3 x 8 = 24\n3 x 9 = 27\n3 x 10 = 30'),
(41,'4\n1 2 3 4','10'),(41,'3\n5 10 15','30'),(42,'5\n3 1 4 1 5','5'),(42,'4\n10 2 3 8','10'),
(43,'4\n3 1 4 2','1'),(43,'3\n5 10 3','3'),(44,'4\n1 2 3 4','4 3 2 1'),(44,'3\n5 10 15','15 10 5'),
(45,'hello','5'),(45,'abcde','5'),(46,'7','Prime'),(46,'4','Not Prime'),
(47,'6','0 1 1 2 3 5'),(47,'5','0 1 1 2 3'),
(48,'121','Palindrome'),(48,'123','Not Palindrome');

-- DSA Q49-64
INSERT INTO testcases (question_id,input,expected_output) VALUES
(49,'5\n1 2 3 4 5\n3','2'),(49,'5\n1 2 3 4 5\n6','-1'),
(50,'5\n1 3 5 7 9\n5','2'),(50,'5\n1 3 5 7 9\n4','-1'),
(51,'5\n3 1 4 2 5','4'),(51,'4\n1 2 3 4','3'),
(52,'5\n1 2 3 2 1\n2','2'),(52,'4\n1 1 1 1\n1','4'),
(53,'5\n5 3 1 4 2','1 2 3 4 5'),(53,'4\n4 3 2 1','1 2 3 4'),
(54,'5\n5 3 1 4 2','1 2 3 4 5'),(54,'4\n4 3 2 1','1 2 3 4'),
(55,'5\n5 3 1 4 2','1 2 3 4 5'),(56,'5\n5 3 1 4 2','1 2 3 4 5'),
(57,'5\npush 1\npush 2\npop\npush 3\npop','2\n3'),
(58,'({[]})','Balanced'),(58,'({[)})','Unbalanced'),
(59,'4\nenqueue 1\nenqueue 2\ndequeue\ndequeue','1\n2'),
(60,'4\n1 2 3 4','4 3 2 1'),(61,'4\n1 2 3 4','10'),
(62,'3\n1 2\n1 3','2'),(63,'7\n4 2 6 1 3 5 7','1 2 3 4 5 6 7'),
(64,'5\n1 2\n1 3\n3 4\n3 5','3');

-- Adv DSA Q65-80
INSERT INTO testcases (question_id,input,expected_output) VALUES
(65,'7','13'),(65,'0','0'),(65,'1','1'),
(66,'10\n3\n4 5\n3 4\n2 3','9'),
(67,'abcde\nace','3'),(67,'AGGTAB\nGXTXAYB','4'),
(68,'3\n1 5 6\n11','3'),(68,'2\n1 2\n3','3'),
(69,'4\n3\n1 2\n2 3\n3 4\n1','1 2 3 4'),
(70,'4\n3\n1 2\n2 3\n3 4\n1','1 2 4 3'),
(71,'4\n4\n1 2\n2 3\n3 4\n4 1','Cycle'),(71,'3\n2\n1 2\n2 3','No Cycle'),
(72,'4\n4\n1 2\n2 3\n1 3\n3 4\n1 4','2'),
(73,'10','2'),(74,'7\n4 2 6 1 3 5 7\n1\n1 7','4'),
(75,'7\n4 2 1 3 6 5 7','16'),(76,'5\n1 2\n1 3\n3 4\n3 5','6'),
(77,'3\nhello\nworld\nhell\n2\nhell\nhola','Found\nNot Found'),
(78,'5\n1 2 3 4 5\n1\n2 4','9'),(79,'6\n3 1 2 5 4 6\n3','5 4 6'),
(80,'4\n4\n1 2\n2 3\n3 4\n4 2','1 2 3 4');

-- DBMS Q81-96
INSERT INTO testcases (question_id,input,expected_output) VALUES
(81,'','5'),(82,'3\nBob\nAlice\nCarol','Alice\nBob\nCarol'),
(83,'5\n1 2 3 4 5','2\n4'),(84,'4\n100 200 150 250','700'),
(85,'2\n1 Alice\n2 Bob\n2\n2 Baz\n3 Qux','Alice Baz'),
(86,'5\n1 2 2 3 3','1 2 3'),(87,'4\ndog\ncat\ndog\nbird','bird 1\ncat 1\ndog 2'),
(88,'4\nA 10\nA 20\nB 5\nB 15','A 20\nB 15'),
(89,'A B\nA->B\nA','A B'),(90,'3\nA B C\n1\nA->BC\nA','A'),
(91,'2\nA->BC\nBC->D','0'),(92,'A->B\nB->C','Yes'),
(93,'4\nT1:R(x)\nT2:W(x)\nT1:W(x)\nT2:R(x)','Conflict'),
(94,'3\n5\n10 20 30 40 50','2'),(95,'3\n2\nT1 T2\nT2 T3','No Deadlock'),
(96,'T1:R(x)\nT2:W(x)\nT1:R(x)','Dirty Read');

-- DAA Q97-112
INSERT INTO testcases (question_id,input,expected_output) VALUES
(97,'10 10','O(N)'),(97,'100 200','O(N)'),
(98,'5\n1 3 5 7 9\n5','2'),(98,'5\n1 3 5 7 9\n4','-1'),
(99,'5\n5 3 1 4 2','1 2 3 4 5'),(100,'5\n-2 1 -3 4 -1','4'),
(101,'3\n1 4\n3 5\n0 6','2'),(102,'3\n10\n10 60\n20 100\n30 120','240.00'),
(103,'4\na 5\nb 9\nc 12\nd 13','Total Cost: 144'),
(104,'4\n5\n1 2 10\n1 3 6\n2 4 5\n3 4 15\n3 2 4','19'),
(105,'abcde\nace','3'),(105,'AGGTAB\nGXTXAYB','4'),
(106,'4\n40 20 30 10','26000'),(107,'kitten\nsitting','3'),
(108,'4\n1 5 8 9','10'),
(109,'4\n5\n1 2 10\n1 3 6\n2 4 5\n3 4 15\n3 2 4\n1','0 10 6 11 15'),
(110,'3\n0 1000 3\n1000 0 1\n3 1 0','0 4 3\n4 0 1\n3 1 0'),
(111,'4\n4\n1 2\n2 3\n3 4\n4 2','1 2 3 4'),
(112,'3\n3\n1 2\n1 3\n2 3\n3','Possible');

-- Placement Q113-128
INSERT INTO testcases (question_id,input,expected_output) VALUES
(113,'12 18','6'),(113,'7 5','1'),(114,'4 6','12'),(114,'3 7','21'),
(115,'10','4'),(115,'20','8'),(116,'6','Perfect'),(116,'7','Not Perfect'),
(117,'3','1.83'),(117,'5','2.28'),(118,'123','3'),(118,'9999','4'),
(119,'153','Armstrong'),(119,'100','Not Armstrong'),
(120,'10','1010'),(120,'8','1000'),
(121,'listen\nsilent','Anagram'),(121,'hello\nworld','Not Anagram'),
(122,'aaabb','a3b2'),(122,'aabccc','a2b1c3'),
(123,'5\n1 2 4 5','3'),(123,'6\n1 2 3 5 6','4'),
(124,'5\n3\n1 2 3 4 5','4 5 1 2 3'),(124,'4\n1\n1 2 3 4','4 1 2 3'),
(125,'4\n2 7 11 15\n9','0 1'),(126,'6\n0 1 0 2 1 0','3'),
(127,'()[]{}','Valid'),(127,'([)]','Invalid'),
(128,'3','1 2 3\n8 9 4\n7 6 5');

-- ============================================================
-- MODULES (4 per subject = 32 total)
-- ============================================================
INSERT INTO modules (subject_id,title,description,order_num) VALUES
(1,'Module 1 — Java Basics','Hello World, I/O, Conditions, Operators',1),
(1,'Module 2 — Strings and Arrays','String ops, Array manipulation',2),
(1,'Module 3 — OOP Concepts','Factorial, Fibonacci, Prime, Power of 2',3),
(1,'Module 4 — Java Algorithms','Binary Search, Sorting, Palindrome',4),
(2,'Module 1 — Python Basics','Hello World, Arithmetic, Conditions',1),
(2,'Module 2 — Strings','String operations, manipulation',2),
(2,'Module 3 — Logic and Loops','Factorial, Prime, Fibonacci, Digits',3),
(2,'Module 4 — Lists and Functions','List ops, Sort, Matrix',4),
(3,'Module 1 — C Basics','I/O, Conditions, Calculator',1),
(3,'Module 2 — Loops and Math','Factorial, Digits, Reverse',2),
(3,'Module 3 — Arrays','Sum, Max, Min, Reverse',3),
(3,'Module 4 — Strings and Advanced','Length, Prime, Fibonacci',4),
(4,'Module 1 — Searching','Linear, Binary, Occurrence',1),
(4,'Module 2 — Sorting Algorithms','Bubble, Selection, Insertion, Merge',2),
(4,'Module 3 — Stack and Queue','Push/Pop, Balanced, Queue ops',3),
(4,'Module 4 — Trees and Lists','Linked List, Trees, Traversal',4),
(5,'Module 1 — Dynamic Programming','Fibonacci, Knapsack, LCS, Coin Change',1),
(5,'Module 2 — Graph Algorithms','BFS, DFS, Cycle Detection, Shortest Path',2),
(5,'Module 3 — Advanced Trees','AVL, LCA, Level Order, Diameter',3),
(5,'Module 4 — Expert Techniques','Trie, Segment Tree, Sliding Window, Topo Sort',4),
(6,'Module 1 — SQL Basics','SELECT, COUNT, SUM, FILTER',1),
(6,'Module 2 — Joins and Aggregates','JOIN, GROUP BY, DISTINCT',2),
(6,'Module 3 — Normalization','FD Closure, 3NF, Candidate Keys',3),
(6,'Module 4 — Transactions','ACID, B-Tree, Deadlock, Isolation',4),
(7,'Module 1 — Complexity and Divide','Big-O, Binary Search, Merge Sort',1),
(7,'Module 2 — Greedy Algorithms','Activity, Fractional Knapsack, Huffman, MST',2),
(7,'Module 3 — Dynamic Programming','LCS, Matrix Chain, Edit Distance, Rod Cutting',3),
(7,'Module 4 — Graph and NP','Dijkstra, Floyd-Warshall, Topo Sort, Coloring',4),
(8,'Module 1 — Number Theory','GCD, LCM, Primes, Perfect Numbers',1),
(8,'Module 2 — Series and Patterns','Digit ops, Armstrong, Binary',2),
(8,'Module 3 — Logical Reasoning','Anagram, Compression, Missing Number',3),
(8,'Module 4 — Coding Rounds','Two Sum, Rain Water, Spiral Matrix',4);

-- ============================================================
-- MODULE-QUESTION MAPPING
-- ============================================================
-- Java: mod1-4 maps to Q1-16
INSERT INTO module_questions (module_id,question_id,order_num) VALUES
(1,1,1),(1,2,2),(1,3,3),(1,4,4),
(2,5,1),(2,6,2),(2,7,3),(2,8,4),
(3,9,1),(3,10,2),(3,11,3),(3,12,4),
(4,13,1),(4,14,2),(4,15,3),(4,16,4);
-- Python: mod5-8 maps to Q17-32
INSERT INTO module_questions (module_id,question_id,order_num) VALUES
(5,17,1),(5,18,2),(5,19,3),(5,20,4),
(6,21,1),(6,22,2),(6,23,3),(6,24,4),
(7,25,1),(7,26,2),(7,27,3),(7,28,4),
(8,29,1),(8,30,2),(8,31,3),(8,32,4);
-- C: mod9-12 maps to Q33-48
INSERT INTO module_questions (module_id,question_id,order_num) VALUES
(9,33,1),(9,34,2),(9,35,3),(9,36,4),
(10,37,1),(10,38,2),(10,39,3),(10,40,4),
(11,41,1),(11,42,2),(11,43,3),(11,44,4),
(12,45,1),(12,46,2),(12,47,3),(12,48,4);
-- DSA: mod13-16 maps to Q49-64
INSERT INTO module_questions (module_id,question_id,order_num) VALUES
(13,49,1),(13,50,2),(13,51,3),(13,52,4),
(14,53,1),(14,54,2),(14,55,3),(14,56,4),
(15,57,1),(15,58,2),(15,59,3),(15,60,4),
(16,61,1),(16,62,2),(16,63,3),(16,64,4);
-- Adv DSA: mod17-20 maps to Q65-80
INSERT INTO module_questions (module_id,question_id,order_num) VALUES
(17,65,1),(17,66,2),(17,67,3),(17,68,4),
(18,69,1),(18,70,2),(18,71,3),(18,72,4),
(19,73,1),(19,74,2),(19,75,3),(19,76,4),
(20,77,1),(20,78,2),(20,79,3),(20,80,4);
-- DBMS: mod21-24 maps to Q81-96
INSERT INTO module_questions (module_id,question_id,order_num) VALUES
(21,81,1),(21,82,2),(21,83,3),(21,84,4),
(22,85,1),(22,86,2),(22,87,3),(22,88,4),
(23,89,1),(23,90,2),(23,91,3),(23,92,4),
(24,93,1),(24,94,2),(24,95,3),(24,96,4);
-- DAA: mod25-28 maps to Q97-112
INSERT INTO module_questions (module_id,question_id,order_num) VALUES
(25,97,1),(25,98,2),(25,99,3),(25,100,4),
(26,101,1),(26,102,2),(26,103,3),(26,104,4),
(27,105,1),(27,106,2),(27,107,3),(27,108,4),
(28,109,1),(28,110,2),(28,111,3),(28,112,4);
-- Placement: mod29-32 maps to Q113-128
INSERT INTO module_questions (module_id,question_id,order_num) VALUES
(29,113,1),(29,114,2),(29,115,3),(29,116,4),
(30,117,1),(30,118,2),(30,119,3),(30,120,4),
(31,121,1),(31,122,2),(31,123,3),(31,124,4),
(32,125,1),(32,126,2),(32,127,3),(32,128,4);

-- ============================================================
-- EXAMS: No start/end time restrictions — always open
-- Entry/Exit passwords set per subject
-- Total marks: 30 (Part A: 6×1=6, Part B: 2×7=14, Part C: 1×10=10)
-- ============================================================
INSERT INTO exams (subject_id,title,duration_min,total_marks,is_active,entry_password,exit_password,start_time,end_time) VALUES
(1,'Java Mid-Term Examination',        90,30,1,'java123',  'exitjava', NULL,NULL),
(2,'Python Mid-Term Examination',      90,30,1,'py123',    'exitpy',   NULL,NULL),
(3,'C Programming Mid-Term Exam',      90,30,1,'cprog123', 'exitcprog',NULL,NULL),
(4,'DSA Mid-Term Examination',         90,30,1,'dsa123',   'exitdsa',  NULL,NULL),
(5,'Advanced DSA Mid-Term Exam',       90,30,1,'adsa123',  'exitadsa', NULL,NULL),
(6,'DBMS Mid-Term Examination',        90,30,1,'dbms123',  'exitdbms', NULL,NULL),
(7,'DAA Mid-Term Examination',         90,30,1,'daa123',   'exitdaa',  NULL,NULL),
(8,'Placement Training Exam',          90,30,1,'place123', 'exitplace',NULL,NULL);

-- ============================================================
-- PART A: MCQ Questions (6 per exam × 1 mark each = 6 marks)
-- ============================================================

-- Java MCQs (exam_id=1)
INSERT INTO exam_mcq_questions (exam_id,question,opt_a,opt_b,opt_c,opt_d,answer,marks,order_num) VALUES
(1,'Which keyword is used to define a class in Java?','define','class','struct','object','B',1,1),
(1,'What is the default value of an int variable in Java?','null','0','undefined','false','B',1,2),
(1,'Which of the following is NOT a Java primitive type?','int','float','String','boolean','C',1,3),
(1,'JVM stands for?','Java Visual Machine','Java Virtual Machine','Java Version Manager','Java Value Method','B',1,4),
(1,'What is the output of System.out.println(10/3)?','3.33','3','4','Error','B',1,5),
(1,'Which method is the entry point of a Java program?','start()','main()','init()','run()','B',1,6);

-- Python MCQs (exam_id=2)
INSERT INTO exam_mcq_questions (exam_id,question,opt_a,opt_b,opt_c,opt_d,answer,marks,order_num) VALUES
(2,'What does the print() function do in Python?','Reads input','Displays output','Declares variable','None of these','B',1,1),
(2,'Which keyword is used to define a function in Python?','function','define','def','func','C',1,2),
(2,'What is the output of print(2**3)?','6','8','9','None','B',1,3),
(2,'Which data type is used to store True/False values?','int','float','bool','str','C',1,4),
(2,'What is the result of len("hello")?','4','5','6','None','B',1,5),
(2,'Which symbol is used for comments in Python?','//','/*','#','--','C',1,6);

-- C Programming MCQs (exam_id=3)
INSERT INTO exam_mcq_questions (exam_id,question,opt_a,opt_b,opt_c,opt_d,answer,marks,order_num) VALUES
(3,'Which header file is needed to use printf()?','stdlib.h','math.h','stdio.h','string.h','C',1,1),
(3,'What does scanf() do in C?','Prints output','Reads input','Declares array','None','B',1,2),
(3,'What is sizeof(int) on a 32-bit system?','1','2','4','8','C',1,3),
(3,'Which operator is used to declare a pointer?','&','*','->','All of these','B',1,4),
(3,'What is the return type of main() in C?','void','int','char','float','B',1,5),
(3,'Which loop is guaranteed to execute at least once?','for','while','do-while','None','C',1,6);

-- DSA MCQs (exam_id=4)
INSERT INTO exam_mcq_questions (exam_id,question,opt_a,opt_b,opt_c,opt_d,answer,marks,order_num) VALUES
(4,'What is the time complexity of Binary Search?','O(N)','O(log N)','O(N^2)','O(N log N)','B',1,1),
(4,'Which data structure follows LIFO principle?','Queue','Tree','Stack','Heap','C',1,2),
(4,'Which sorting algorithm is both stable and in-place?','Merge Sort','Quick Sort','Insertion Sort','Heap Sort','C',1,3),
(4,'What is the height of a complete binary tree with N nodes?','N','log N','N/2','N^2','B',1,4),
(4,'BFS uses which data structure?','Stack','Queue','Tree','Heap','B',1,5),
(4,'What is the worst case time complexity of Quick Sort?','O(N)','O(N log N)','O(N^2)','O(log N)','C',1,6);

-- Advanced DSA MCQs (exam_id=5)
INSERT INTO exam_mcq_questions (exam_id,question,opt_a,opt_b,opt_c,opt_d,answer,marks,order_num) VALUES
(5,'Dynamic Programming is based on which principle?','Greedy choice','Optimal substructure','Divide only','None','B',1,1),
(5,'LCS of "ABCBDAB" and "BDCAB" has length?','4','5','3','6','A',1,2),
(5,'Dijkstra algorithm fails with?','Positive weights','Negative weights','Zero weights','All weights','B',1,3),
(5,'What is the balance factor range in an AVL tree?','-1 to 1','-2 to 2','0 to 2','-1 to 2','A',1,4),
(5,'Trie lookup time for a string of length L?','O(N)','O(L)','O(log N)','O(1)','B',1,5),
(5,'What is the update time of a segment tree?','O(1)','O(N)','O(log N)','O(N log N)','C',1,6);

-- DBMS MCQs (exam_id=6)
INSERT INTO exam_mcq_questions (exam_id,question,opt_a,opt_b,opt_c,opt_d,answer,marks,order_num) VALUES
(6,'DBMS stands for?','Data Base Management System','Data Byte Management System','Digital Base Machine Software','None','A',1,1),
(6,'SQL stands for?','Structured Query Language','Simple Query Language','Sequential Query Language','None','A',1,2),
(6,'Which SQL clause is used to filter rows?','ORDER BY','GROUP BY','WHERE','HAVING','C',1,3),
(6,'Normalization is used to reduce?','Data Speed','Data Redundancy','Data Security','Query Time','B',1,4),
(6,'ACID in DBMS stands for?','Atomicity Consistency Isolation Durability','All Complete In Data','Auto Commit Insert Delete','None','A',1,5),
(6,'Which key uniquely identifies a record in a table?','Foreign Key','Primary Key','Super Key','Candidate Key','B',1,6);

-- DAA MCQs (exam_id=7)
INSERT INTO exam_mcq_questions (exam_id,question,opt_a,opt_b,opt_c,opt_d,answer,marks,order_num) VALUES
(7,'Divide and Conquer approach means?','Greedy method','Split, solve, merge','Dynamic approach','None','B',1,1),
(7,'Greedy algorithm makes?','Always globally optimal choice','Locally optimal choice','Random choice','None','B',1,2),
(7,'Huffman encoding is an example of?','Greedy','Dynamic Programming','Divide and Conquer','Backtracking','A',1,3),
(7,'What is the time complexity of Merge Sort?','O(N)','O(N log N)','O(N^2)','O(log N)','B',1,4),
(7,'Dijkstra algorithm is a type of?','Dynamic Programming','Greedy','Backtracking','Divide and Conquer','B',1,5),
(7,'Edit distance problem is solved using?','Greedy','Dynamic Programming','Divide and Conquer','Graph','B',1,6);

-- Placement MCQs (exam_id=8)
INSERT INTO exam_mcq_questions (exam_id,question,opt_a,opt_b,opt_c,opt_d,answer,marks,order_num) VALUES
(8,'What is the GCD of 12 and 18?','4','6','3','9','B',1,1),
(8,'153 is an Armstrong number because?','1+5+3=9','1^3+5^3+3^3=153','153/3=51','None','B',1,2),
(8,'Optimal time complexity for Two Sum problem?','O(N^2)','O(N)','O(N log N)','O(log N)','B',1,3),
(8,'What is the binary representation of 10?','1010','1100','1001','1110','A',1,4),
(8,'Run-length encoding of "aaabb" is?','a3b2','a2ab3','3a2b','a3bb2','A',1,5),
(8,'Trapping Rain Water problem is solved using?','Greedy','Stack or DP','BFS','None','B',1,6);

-- ============================================================
-- PART B: Coding Questions (2 per exam × 7 marks = 14 marks)
-- Maps to existing questions in questions table
-- ============================================================
INSERT INTO exam_coding_questions (exam_id,question_id,part,marks,order_num) VALUES
(1,9,'B',7,1),(1,11,'B',7,2),
(2,25,'B',7,1),(2,26,'B',7,2),
(3,37,'B',7,1),(3,46,'B',7,2),
(4,53,'B',7,1),(4,58,'B',7,2),
(5,65,'B',7,1),(5,67,'B',7,2),
(6,81,'B',7,1),(6,82,'B',7,2),
(7,99,'B',7,1),(7,105,'B',7,2),
(8,113,'B',7,1),(8,121,'B',7,2);

-- Part C coding questions (1 per exam × 10 marks) — harder coding problems
INSERT INTO exam_coding_questions (exam_id,question_id,part,marks,order_num) VALUES
(1,15,'C',10,1),
(2,30,'C',10,1),
(3,47,'C',10,1),
(4,63,'C',10,1),
(5,79,'C',10,1),
(6,95,'C',10,1),
(7,111,'C',10,1),
(8,127,'C',10,1);

-- ============================================================
-- PART C: Long Answer Questions (1 per exam × 10 marks = 10 marks)
-- ============================================================
CREATE TABLE IF NOT EXISTS exam_long_questions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  exam_id INT NOT NULL,
  question TEXT NOT NULL,
  marks INT DEFAULT 10,
  order_num INT DEFAULT 1,
  FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS exam_long_answers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  attempt_id INT NOT NULL,
  lq_id INT NOT NULL,
  answer_text LONGTEXT,
  score INT DEFAULT 0,
  UNIQUE KEY uq_attempt_lq (attempt_id,lq_id),
  FOREIGN KEY (attempt_id) REFERENCES exam_attempts(id) ON DELETE CASCADE,
  FOREIGN KEY (lq_id) REFERENCES exam_long_questions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

ALTER TABLE exam_attempts ADD COLUMN IF NOT EXISTS score_long INT DEFAULT 0;

INSERT INTO exam_long_questions (exam_id,question,marks,order_num) VALUES
(1,'Explain the four pillars of Object-Oriented Programming in Java — Encapsulation, Abstraction, Inheritance, and Polymorphism. Give a real-world example for each and write a small Java class hierarchy that demonstrates at least two of these concepts.',10,1),
(2,'Explain the difference between list, tuple, set, and dictionary in Python. When would you use each data structure? Write a Python program that uses all four to solve a student grade management problem.',10,1),
(3,'Explain pointers in C programming in detail. What are common pointer mistakes and how to avoid them? Write a C program using pointers to implement a swap function and demonstrate dynamic memory allocation with malloc() and free().',10,1),
(4,'Compare Bubble Sort, Merge Sort, and Quick Sort. Provide the time and space complexity for each in best, average, and worst cases. Explain when you would prefer one algorithm over another with suitable examples.',10,1),
(5,'Explain Dynamic Programming with the concept of memoization vs tabulation. Write the solution to the 0/1 Knapsack problem using DP tabulation approach and analyze its time and space complexity step by step.',10,1),
(6,'Explain database normalization. Define 1NF, 2NF, and 3NF with examples. Take an unnormalized relation of your choice (e.g., student-course data) and normalize it step by step up to 3NF, explaining the functional dependencies at each stage.',10,1),
(7,'Explain the concept of NP-completeness. What is the difference between P, NP, and NP-Complete? Give three real-world examples of NP-complete problems and explain how approximation algorithms are used to handle them practically.',10,1),
(8,'Describe the STAR method for behavioral interview questions and give one example answer. Then explain how you would approach a system design question: "Design a URL Shortener". Include architecture components, database schema, and how you would handle scalability.',10,1);

