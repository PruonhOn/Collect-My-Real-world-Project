// Owner: Lin Sreydeth - Modal component
// src/components/ui/Modal.jsx

import React from "react";
import { X } from "lucide-react";

const Modal = ({ children, onClose, title, size = "md" }) => {
  const sizeClasses = {
    sm: "max-w-md",
    md: "max-w-2xl",
    lg: "max-w-4xl",
    xl: "max-w-6xl",
  };

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div
        className={`bg-white rounded-xl shadow-2xl w-full ${sizeClasses[size]} max-h-[90vh] overflow-hidden`}
      >
        <div className="flex items-center justify-between p-6 border-b">
          <h3 className="text-xl font-bold text-gray-800">{title}</h3>
          <button
            onClick={onClose}
            className="text-gray-400 hover:text-gray-600 transition-colors"
          >
            <X className="w-6 h-6" />
          </button>
        </div>

        <div className="p-6 overflow-y-auto max-h-[calc(90vh-80px)]">
          {children}
        </div>
      </div>
    </div>
  );
};

// ===============================
// ✅ ConfirmModal Component Added
// ===============================
const ConfirmModal = ({ title, message, onConfirm, onCancel }) => {
  return (
    <Modal title={title} onClose={onCancel} size="sm">
      <p className="text-gray-700 mb-6">{message}</p>

      <div className="flex justify-end space-x-3">
        <button
          onClick={onCancel}
          className="px-4 py-2 rounded-lg bg-gray-200 hover:bg-gray-300 transition"
        >
          Cancel
        </button>

        <button
          onClick={onConfirm}
          className="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700 transition"
        >
          Confirm
        </button>
      </div>
    </Modal>
  );
};

// Export default + named export
export default Modal;
export { ConfirmModal };
