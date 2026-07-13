<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireRole('teacher');

$db         = getDB();
$user       = currentUser();
$teacher_id = $user['id'];
$msg        = $err = '';

// POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Delete course
    if ($action === 'delete_course') {
        $course_id = (int)($_POST['course_id'] ?? 0);
        if ($course_id) {
            $stmt = $db->prepare("DELETE FROM courses WHERE id=? AND teacher_id=?");
            $stmt->execute([$course_id, $teacher_id]);
            $msg = $stmt->rowCount() ? 'Cours supprimé.' : 'Impossible de supprimer.';
        }
    }

    // Add lesson
    elseif ($action === 'add_lesson') {
        $course_id = (int)($_POST['course_id'] ?? 0);
        $titre     = trim($_POST['titre'] ?? '');
        $type      = $_POST['type'] ?? '';
        $ordre     = (int)($_POST['ordre'] ?? 1);

        $chk = $db->prepare("SELECT id FROM courses WHERE id=? AND teacher_id=? LIMIT 1");
        $chk->execute([$course_id, $teacher_id]);

        if ($chk->fetch() && $titre && in_array($type, ['pdf','video']) && isset($_FILES['file'])) {
            $file = $_FILES['file'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed  = ($type === 'pdf') ? ['pdf'] : ['mp4','mov','webm','avi'];
                if (in_array($ext, $allowed)) {
                    $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name']));
                    $dest_dir = ($type === 'pdf') ? UPLOAD_PDF : UPLOAD_VIDEO;
                    if (!is_dir($dest_dir)) mkdir($dest_dir, 0777, true);
                    if (move_uploaded_file($file['tmp_name'], $dest_dir . $filename)) {
                        $db->prepare("INSERT INTO lessons (course_id, titre, type, fichier, ordre) VALUES (?,?,?,?,?)")
                           ->execute([$course_id, $titre, $type, $filename, $ordre]);
                        $msg = 'Leçon ajoutée avec succès.';
                    } else {
                        $err = 'Erreur lors du déplacement du fichier.';
                    }
                } else {
                    $err = 'Format de fichier non autorisé.';
                }
            } else {
                $err = 'Erreur upload (code: ' . $file['error'] . ').';
            }
        } else {
            $err = 'Tous les champs et le fichier sont requis.';
        }
    }

    // Delete lesson
    elseif ($action === 'delete_lesson') {
        $lesson_id = (int)($_POST['lesson_id'] ?? 0);
        if ($lesson_id) {
            $stmt = $db->prepare("DELETE l FROM lessons l JOIN courses c ON c.id=l.course_id WHERE l.id=? AND c.teacher_id=?");
            $stmt->execute([$lesson_id, $teacher_id]);
            $msg = $stmt->rowCount() ? 'Leçon supprimée.' : 'Erreur.';
        }
    }

    // Add quiz
    elseif ($action === 'add_quiz') {
        $lesson_id = (int)($_POST['lesson_id'] ?? 0);
        $titre     = trim($_POST['titre'] ?? '');
        $passing   = (int)($_POST['passing_score'] ?? 50);

        $chk = $db->prepare("SELECT l.id FROM lessons l JOIN courses c ON c.id=l.course_id WHERE l.id=? AND c.teacher_id=? LIMIT 1");
        $chk->execute([$lesson_id, $teacher_id]);
        if ($chk->fetch() && $titre) {
            $db->prepare("INSERT INTO quizzes (lesson_id, titre, passing_score) VALUES (?,?,?)")
               ->execute([$lesson_id, $titre, $passing]);
            $msg = 'Quiz créé. Ajoutez maintenant des questions.';
        } else {
            $err = 'Leçon invalide ou titre manquant.';
        }
    }

    // Add question
    elseif ($action === 'add_question') {
        $quiz_id  = (int)($_POST['quiz_id'] ?? 0);
        $question = trim($_POST['question'] ?? '');
        $opt_a    = trim($_POST['option_a'] ?? '');
        $opt_b    = trim($_POST['option_b'] ?? '');
        $opt_c    = trim($_POST['option_c'] ?? '');
        $opt_d    = trim($_POST['option_d'] ?? '');
        $correct  = $_POST['bonne_reponse'] ?? '';

        $chk = $db->prepare("SELECT q.id FROM quizzes q JOIN lessons l ON l.id=q.lesson_id JOIN courses c ON c.id=l.course_id WHERE q.id=? AND c.teacher_id=? LIMIT 1");
        $chk->execute([$quiz_id, $teacher_id]);

        if ($chk->fetch() && $question && $opt_a && $opt_b && $opt_c && $opt_d && in_array($correct, ['A','B','C','D'])) {
            $db->prepare("INSERT INTO quiz_questions (quiz_id, question, option_a, option_b, option_c, option_d, bonne_reponse) VALUES (?,?,?,?,?,?,?)")
               ->execute([$quiz_id, $question, $opt_a, $opt_b, $opt_c, $opt_d, $correct]);
            $msg = 'Question ajoutée.';
        } else {
            $err = 'Tous les champs sont requis.';
        }
    }
}

// Fetch all courses with lessons
$stmt_courses = $db->prepare("
    SELECT c.*, m.titre AS module_titre
    FROM courses c JOIN modules m ON m.id=c.module_id
    WHERE c.teacher_id=?
    ORDER BY c.created_at DESC
");
$stmt_courses->execute([$teacher_id]);
$courses = $stmt_courses->fetchAll();

$courses_data = [];
foreach ($courses as $c) {
    $stmt_l = $db->prepare("
        SELECT l.*, q.id AS quiz_id, q.titre AS quiz_titre, q.passing_score,
               (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id=q.id) AS nb_questions
        FROM lessons l
        LEFT JOIN quizzes q ON q.lesson_id=l.id
        WHERE l.course_id=?
        ORDER BY l.ordre ASC, l.id ASC
    ");
    $stmt_l->execute([$c['id']]);
    $c['lessons'] = $stmt_l->fetchAll();
    $courses_data[] = $c;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SnowLearn — Mes Cours</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="shell">
  <?php include '../includes/sidebar_teacher.php'; ?>
  <div class="main-content">
    <header class="topbar">
      <button class="menu-toggle"><svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <span class="topbar-title">Mes Cours</span>
      <div class="topbar-right">
        <?php if (isset($_GET['created'])): ?><div class="alert alert-success" style="margin:0;padding:8px 14px;">Cours créé !</div><?php endif; ?>
        <a href="create_course.php" class="btn btn-primary btn-sm">
          <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Nouveau cours
        </a>
      </div>
    </header>
    <main class="page-body">
      <?php if ($msg): ?><div class="alert alert-success"><?= sanitize($msg) ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert alert-danger"><?= sanitize($err) ?></div><?php endif; ?>

      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
        <div>
          <h2 style="font-size:1.4rem;">Mes Cours <span style="color:var(--text-muted);font-weight:400;">(<?= count($courses_data) ?>)</span></h2>
        </div>
        <input class="form-control" type="search" id="search-courses" placeholder="Rechercher un cours…" style="max-width:280px;">
      </div>

      <?php if (empty($courses_data)): ?>
      <div class="card"><div style="padding:60px;text-align:center;color:var(--text-muted);">
        <svg viewBox="0 0 24 24" width="48" height="48" stroke="currentColor" fill="none" stroke-width="1.5" style="margin:0 auto 16px;opacity:.3;display:block;"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
        <p>Aucun cours créé. <a href="create_course.php" style="color:var(--primary);">Créez votre premier cours</a></p>
      </div></div>

      <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:16px;" id="courses-list">
        <?php foreach ($courses_data as $c): ?>
        <div class="card course-item" data-title="<?= strtolower(sanitize($c['titre'])) ?>">
          <!-- Course header -->
          <div style="cursor:pointer;display:flex;justify-content:space-between;align-items:center;padding:18px 22px;background:var(--surface2);" onclick="toggleLessons(<?= $c['id'] ?>)">
            <div>
              <div style="font-family:'Outfit',sans-serif;font-weight:700;font-size:1rem;"><?= sanitize($c['titre']) ?></div>
              <div style="font-size:.78rem;color:var(--text-muted);margin-top:3px;">
                <span class="badge badge-violet" style="font-size:.65rem;"><?= sanitize($c['module_titre']) ?></span>
                <span style="margin-left:8px;"><?= count($c['lessons']) ?> leçon(s)</span>
              </div>
            </div>
            <div style="display:flex;gap:8px;align-items:center;" onclick="event.stopPropagation()">
              <button class="btn btn-primary btn-sm"
                onclick="openAddLessonModal(<?= $c['id'] ?>, '<?= addslashes(sanitize($c['titre'])) ?>')">
                <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Leçon
              </button>
              <form method="POST" onsubmit="return confirm('Supprimer ce cours et toutes ses leçons ?')">
                <input type="hidden" name="action" value="delete_course">
                <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
                <button class="btn btn-danger btn-sm btn-icon" title="Supprimer">
                  <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                </button>
              </form>
            </div>
          </div>

          <!-- Lessons list -->
          <div id="lessons-<?= $c['id'] ?>" style="display:none;">
            <?php if (empty($c['lessons'])): ?>
            <div style="padding:20px;text-align:center;font-size:.85rem;color:var(--text-muted);">Aucune leçon. Cliquez sur « Leçon » pour en ajouter.</div>
            <?php else: foreach ($c['lessons'] as $l): ?>
            <div style="display:flex;align-items:flex-start;gap:14px;padding:14px 22px;border-top:1px solid var(--border);">
              <div style="width:28px;height:28px;border-radius:50%;background:var(--surface3);display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:700;color:var(--text-muted);flex-shrink:0;margin-top:2px;"><?= $l['ordre'] ?></div>
              <div style="flex:1;min-width:0;">
                <div style="font-weight:600;font-size:.875rem;"><?= sanitize($l['titre']) ?></div>
                <div style="font-size:.75rem;color:var(--text-muted);margin-top:2px;">
                  <span class="badge <?= $l['type']==='video' ? 'badge-violet' : 'badge-blue' ?>" style="font-size:.62rem;"><?= $l['type']==='video'?'Vidéo':'PDF' ?></span>
                  <a href="../uploads/<?= $l['type']==='pdf'?'pdfs':'videos' ?>/<?= sanitize($l['fichier']) ?>" target="_blank" style="color:var(--primary);margin-left:8px;font-size:.75rem;"><?= sanitize($l['fichier']) ?></a>
                </div>
                <div style="margin-top:6px;">
                  <?php if ($l['quiz_id']): ?>
                  <span class="badge badge-green" style="font-size:.65rem;">Quiz: <?= sanitize($l['quiz_titre']) ?></span>
                  <span style="font-size:.72rem;color:var(--text-muted);margin-left:6px;">(<?= $l['nb_questions'] ?> question(s))</span>
                  <button class="btn btn-ghost btn-sm" style="padding:2px 8px;font-size:.7rem;margin-left:6px;"
                    onclick="openAddQuestionModal(<?= $l['quiz_id'] ?>, '<?= addslashes(sanitize($l['quiz_titre'])) ?>')">+ Question</button>
                  <?php else: ?>
                  <button class="btn btn-accent btn-sm" style="padding:3px 10px;font-size:.72rem;"
                    onclick="openAddQuizModal(<?= $l['id'] ?>, '<?= addslashes(sanitize($l['titre'])) ?>')">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                    Ajouter un Quiz
                  </button>
                  <?php endif; ?>
                </div>
              </div>
              <form method="POST" onsubmit="return confirm('Supprimer cette leçon ?')">
                <input type="hidden" name="action" value="delete_lesson">
                <input type="hidden" name="lesson_id" value="<?= $l['id'] ?>">
                <button class="btn btn-danger btn-sm btn-icon" title="Supprimer">
                  <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                </button>
              </form>
            </div>
            <?php endforeach; endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </main>
  </div>
</div>

<!-- Modal Add Lesson -->
<div class="modal-overlay" id="modal-add-lesson">
  <div class="modal">
    <div class="modal-header">
      <h3>Ajouter une leçon — <span id="add-lesson-course-name" style="color:var(--primary);"></span></h3>
      <button class="modal-close">✕</button>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="add_lesson">
      <input type="hidden" name="course_id" id="add-lesson-course-id">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Titre de la leçon *</label>
          <input class="form-control" name="titre" required placeholder="Ex : Introduction au HTML">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Type *</label>
            <select class="form-control" name="type" required id="lesson-type-sel" onchange="updateFileHint()">
              <option value="pdf">Document PDF</option>
              <option value="video">Vidéo MP4</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Ordre *</label>
            <input class="form-control" type="number" name="ordre" value="1" min="1" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Fichier *</label>
          <input class="form-control" type="file" name="file" required id="lesson-file-inp">
          <span class="form-hint" id="file-hint">Fichier PDF (.pdf)</span>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost modal-close">Annuler</button>
        <button type="submit" class="btn btn-primary">Ajouter la leçon</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Add Quiz -->
<div class="modal-overlay" id="modal-add-quiz">
  <div class="modal">
    <div class="modal-header">
      <h3>Créer un quiz — <span id="add-quiz-lesson-name" style="color:var(--primary);"></span></h3>
      <button class="modal-close">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add_quiz">
      <input type="hidden" name="lesson_id" id="add-quiz-lesson-id">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Titre du quiz *</label>
          <input class="form-control" name="titre" required placeholder="Ex : Quiz — Introduction HTML">
        </div>
        <div class="form-group">
          <label class="form-label">Score minimum pour valider (%)</label>
          <input class="form-control" type="number" name="passing_score" value="50" min="0" max="100">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost modal-close">Annuler</button>
        <button type="submit" class="btn btn-primary">Créer le quiz</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Add Question -->
<div class="modal-overlay" id="modal-add-question">
  <div class="modal" style="max-width:600px;">
    <div class="modal-header">
      <h3>Nouvelle question — <span id="add-q-quiz-name" style="color:var(--primary);"></span></h3>
      <button class="modal-close">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add_question">
      <input type="hidden" name="quiz_id" id="add-q-quiz-id">
      <div class="modal-body" style="max-height:440px;overflow-y:auto;">
        <div class="form-group">
          <label class="form-label">Énoncé de la question *</label>
          <textarea class="form-control" name="question" required rows="2" placeholder="Quelle est la bonne réponse ?"></textarea>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Option A *</label><input class="form-control" name="option_a" required></div>
          <div class="form-group"><label class="form-label">Option B *</label><input class="form-control" name="option_b" required></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Option C *</label><input class="form-control" name="option_c" required></div>
          <div class="form-group"><label class="form-label">Option D *</label><input class="form-control" name="option_d" required></div>
        </div>
        <div class="form-group">
          <label class="form-label">Bonne réponse *</label>
          <select class="form-control" name="bonne_reponse" required>
            <option value="A">Option A</option>
            <option value="B">Option B</option>
            <option value="C">Option C</option>
            <option value="D">Option D</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost modal-close">Annuler</button>
        <button type="submit" class="btn btn-primary">Enregistrer la question</button>
      </div>
    </form>
  </div>
</div>

<script src="../assets/js/app.js"></script>
<script>
function toggleLessons(id) {
  const el = document.getElementById('lessons-' + id);
  if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

function openAddLessonModal(courseId, courseName) {
  document.getElementById('add-lesson-course-id').value = courseId;
  document.getElementById('add-lesson-course-name').textContent = courseName;
  openModal('modal-add-lesson');
  updateFileHint();
}

function updateFileHint() {
  const sel = document.getElementById('lesson-type-sel');
  const inp = document.getElementById('lesson-file-inp');
  const hint = document.getElementById('file-hint');
  if (sel.value === 'pdf') {
    inp.accept = '.pdf';
    hint.textContent = 'Fichier PDF (.pdf) — max 50MB';
  } else {
    inp.accept = '.mp4,.mov,.webm,.avi';
    hint.textContent = 'Fichier vidéo (.mp4, .mov, .webm) — max 500MB';
  }
}

function openAddQuizModal(lessonId, lessonName) {
  document.getElementById('add-quiz-lesson-id').value = lessonId;
  document.getElementById('add-quiz-lesson-name').textContent = lessonName;
  openModal('modal-add-quiz');
}

function openAddQuestionModal(quizId, quizName) {
  document.getElementById('add-q-quiz-id').value = quizId;
  document.getElementById('add-q-quiz-name').textContent = quizName;
  openModal('modal-add-question');
}

// Search
document.getElementById('search-courses').addEventListener('input', function() {
  const q = this.value.toLowerCase();
  document.querySelectorAll('.course-item').forEach(el => {
    el.style.display = el.dataset.title.includes(q) ? '' : 'none';
  });
});
</script>
</body>
</html>
