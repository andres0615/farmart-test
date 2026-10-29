import type { RefObject } from 'react';

interface Props {
  dialogRef: RefObject<HTMLDialogElement | null>;
  name: string | null;
  onConfirm: () => void;
}

export default function ConfirmDeleteModal({ dialogRef, name, onConfirm }: Props) {
  return (
    <dialog ref={dialogRef} className="modal">
      <div className="modal-box">
        <h3 className="font-bold text-lg">Confirmar eliminación</h3>
        <p className="pt-4 text-sm">
          ¿Estás seguro de eliminar <strong>{name}</strong>? Esta acción no se puede deshacer.
        </p>
        <div className="modal-action">
          <button className="btn" onClick={() => dialogRef.current?.close()}>
            Cancelar
          </button>
          <button
            className="btn btn-error"
            onClick={() => {
              onConfirm();
              dialogRef.current?.close();
            }}
          >
            Eliminar
          </button>
        </div>
      </div>
      <form method="dialog" className="modal-backdrop">
        <button>close</button>
      </form>
    </dialog>
  );
}
